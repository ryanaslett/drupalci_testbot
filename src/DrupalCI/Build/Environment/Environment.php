<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\generic\Command
 *
 * Processes "[build_step]: command:" instructions from within a build definition.
 */

namespace DrupalCI\Build\Environment;

use Docker\API\Model\ContainerConfig;
use Docker\API\Model\ExecConfig;
use Docker\API\Model\ExecStartConfig;
use Docker\API\Model\HostConfig;
use Docker\Manager\ExecManager;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\PluginBase;
use Http\Client\Common\Exception\ClientErrorException;
use Pimple\Container;
use Symfony\Component\Yaml\Yaml;


class Environment implements Injectable, EnvironmentInterface {

  /**
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  /**
   * Stores our Docker Container manager
   *
   * @var \Docker\Docker
   */
  protected $docker;

  // Holds the name and Docker IDs of our executable container.
  protected $executableContainer = [];

  // Holds the name and Docker IDs of our service container.
  protected $serviceContainer;

  /* @var DatabaseInterface */
  protected $database;

  /**
   * @var \Symfony\Component\Yaml\Parser
   *
   *   Parsed Yaml of the build definition.
   */
  protected $yamlparser;

  /* @var \DrupalCI\Build\BuildInterface */
  protected $build;



  public function inject(Container $container) {

    $this->io = $container['console.io'];
    $this->docker = $container['docker'];
    $this->database = $container['db.system'];
    $this->yamlparser = $container['yaml.parser'];
    $this->build = $container['build'];

  }

  /**
   * {@inheritdoc}
   */
  public function executeCommands($commands) {
    // @TODO someday we may have more than one container. This currently assumes
    // just the single Exec container.

    // Data format: 'command [arguments]' or array('command [arguments]', 'command [arguments]')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $commands = is_array($commands) ? $commands : [$commands];


    if (!empty($commands)) {
      // Check that we have a container to execute on
      $container = $this->getExecContainer();

      $id = $container['id'];
      $short_id = substr($id, 0, 8);
      $this->io->writeLn("<info>Executing on container instance $short_id:</info>");
      foreach ($commands as $cmd) {
        $this->io->writeLn("<fg=magenta>$cmd</fg=magenta>");

        $exec_config = new ExecConfig();
        $exec_config->setTty(FALSE);
        $exec_config->setAttachStderr(TRUE);
        $exec_config->setAttachStdout(TRUE);
        $exec_config->setAttachStdin(FALSE);
        $command = ["/bin/bash", "-c", $cmd];
        $exec_config->setCmd($command);

        $exec_manager = $this->docker->getExecManager();
        $response = $exec_manager->create($id, $exec_config);

        $exec_id = $response->getId();
        $this->io->writeLn("<info>Command created as exec id " . substr($exec_id, 0, 8) . "</info>");

        $exec_start_config = new ExecStartConfig();
        $exec_start_config->setTty(FALSE);
        $exec_start_config->setDetach(FALSE);

        $stream = $exec_manager->start($exec_id, $exec_start_config, [], ExecManager::FETCH_STREAM);

        $stdoutFull = "";
        $stderrFull = "";
        $stream->onStdout(function ($stdout) use (&$stdoutFull) {
          $stdoutFull .= $stdout;
          $this->io->write($stdout);
        });
        $stream->onStderr(function ($stderr) use (&$stderrFull) {
          $stderrFull .= $stderr;
          $this->io->write($stderr);
        });
        $stream->wait();

        $exec_command_exit_code = $exec_manager->find($exec_id)->getExitCode();

        if ($this->checkCommandStatus($exec_command_exit_code) !== 0) {
          return $exec_command_exit_code;
        }
      }
    }
    return 0;
  }

  protected function checkCommandStatus($signal) {
    if ($signal !==0) {
      $this->io->drupalCIError('Error', "Received a non-zero return code from the last command executed on the container.  (Return status: " . $signal . ")");
      return 1;
    }
    else {
      return 0;
    }
  }


  protected function getExecContainer() {
    return $this->executableContainer;
  }

  /**
   * @param array $container
   */
  public function startExecContainer($container) {
    $valid = $this->validateImageName($container);
    // 4. If we find a valid container, then we setExecContainers it.
    if (!empty($valid)) {

      $manager = $this->docker->getContainerManager();
      // Get container configuration, which defines parameters such as exposed ports, etc.
      $configs = $this->getContainerConfiguration($container['image']);
      $config = $configs[$container['image']];
      // Add volumes
      $this->createContainerVolumes($config);

      $container_id = $this->createContainer($config);

      // TODO: Ensure there are no stopped containers with the same name (currently throws fatal)
      $response = $manager->start($container_id);
      // TODO: Catch and exception if doesn't return 204.

      $executable_container = $manager->find($container_id);
      $container['id'] = $executable_container->getID();
      $container['name'] = $executable_container->getName();
      $container['ip'] = $executable_container->getNetworkSettings()
        ->getIPAddress();
      $container['created'] = TRUE;
      $short_id = substr($container['id'], 0, 8);
      $this->io->writeln("<comment>Container <options=bold>${container['name']}</options=bold> created from image <options=bold>${container['image']}</options=bold> with ID <options=bold>$short_id</options=bold></comment>");
      $this->executableContainer = $container;
    }
  }



  public function startServiceContainerDaemons($db_container) {
    $valid = $this->validateImageName($db_container);
    // 4. If we find a valid container, then we setExecContainers it.
    if (!empty($valid)) {
      // $container_type is *always* 'db'
      // We don't need to initialize any service container for SQLite.
      if (strpos($this->database->getDbType(), 'sqlite') === 0) {
        return;
      }
      $manager = $this->docker->getContainerManager();

      // Container not running, so we'll need to create it.
      $this->io->writeln("<comment>No active <options=bold>${db_container['image']}</options=bold> service container instances found. Generating new service container.</comment>");

      // Get container configuration, which defines parameters such as exposed ports, etc.
      $configs = $this->getContainerConfiguration($db_container['image']);
      $config = $configs[$db_container['image']];

      $config['HostConfig']['Binds'][0] = $this->build->getDBDirectory() . ':' . $this->database->getDataDir();
      $container_id = $this->createContainer($config);

      // Create the docker container instance, running as a daemon.
      // TODO: Ensure there are no stopped containers with the same name (currently throws fatal)
      $response = $manager->start($container_id);
      // TODO: Catch and exception if doesn't return 204.

      $container = $manager->find($container_id);

      $container_id = $container->getID();
      $container_name = $container->getName();
      $container_ip = $container->getNetworkSettings()->getIPAddress();

      $this->serviceContainer['id'] = $container_id;
      $this->serviceContainer['name'] = $container_name;
      $this->serviceContainer['ip'] = $container_ip;
      $short_id = substr($container_id, 0, 8);
      $this->io->writeln("<comment>Created new <options=bold>${db_container['image']}</options> container instance with ID <options=bold>$short_id</options=bold></comment>");

      // @TODO: should probably add the container environment as a service
      $this->database->setHost($container_ip);
      // @TODO: all of this should probably live inside of the database
      $this->database->connect();
    }

  }

  protected function validateImageName($image_name) {
    // Verify that the appropriate container images exist
    $this->io->writeln("<comment>Validating container images exist</comment>");

    $manager = $this->docker->getImageManager();

    $container_string = explode(':', $image_name['image']);
    $name = $container_string[0];

    try {
      $image = $manager->find($name);
    }
    catch (ClientErrorException $e) {
      $this->io->drupalCIError("Missing Image", "Required container image <options=bold>'$name'</options=bold> not found.");
      return FALSE;
    }
    $id = substr($image->getID(), 0, 8);
    $this->io->writeln("<comment>Found image <options=bold>$name/options=bold> with ID <options=bold>$id</options=bold></comment>");

    return TRUE;
  }

  protected function createContainerVolumes(&$config) {
    $volumes = [];
    // Map working directory
    $working = $this->build->getSourceDirectory();
    // TODO: Change this into defaults, and remove the configuration
    // options.
    // CREATE One for the artifacts directory as well.
    $mount_point = (empty($config['Mountpoint'])) ? "/data" : $config['Mountpoint'];
    $config['HostConfig']['Binds'][] = "$working:$mount_point";
  }

  protected function getContainerConfiguration($image = NULL) {
    // TODO Remove the need for this entirely
    $path = __DIR__ . '/../../Containers';
    // RecursiveDirectoryIterator recurses into directories and returns an
    // iterator for each directory. RecursiveIteratorIterator then iterates over
    // each of the directory iterators, which consecutively return the files in
    // each directory.
    $directory = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
    $configs = [];
    foreach ($directory as $file) {
      if (!$file->isDir() && $file->isReadable() && $file->getExtension() === 'yml') {
        $container_name = $file->getBasename('.yml');
        $dev_suffix = '-dev';
        $isdev = strpos($container_name, $dev_suffix);
        if (!$isdev == 0) {
          $container_name = str_replace('-dev', ':dev', $container_name);
        }
        $image_name = 'drupalci/' . $container_name;
        if (!empty($image) && $image_name != $image) {
          continue;
        }
        // Get the default configuration.
        $container_config = $this->yamlparser->parse(file_get_contents($file->getPathname()));
        $configs[$image_name] = $container_config;
      }
    }
    return $configs;
  }


  /**
   * @param $config
   * @return mixed
   */
  protected function createContainer($config) {
    $manager = $this->docker->getContainerManager();
    $container_config = new ContainerConfig();
    $container_config->setImage($config['Image']);
    $host_config = new HostConfig();
    $host_config->setBinds($config['HostConfig']['Binds']);
    $container_config->setHostConfig($host_config);
    $parameters = [];
    if (!empty($config['name'])) {
      $parameters = ['name' => $config['name']];
    }
    $create_result = $manager->create($container_config, $parameters);
    $container_id = $create_result->getId();
    return $container_id;
  }


}
