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

  // Holds the name and Docker IDs of our executable containers.
  public $executableContainers = [];

  // Holds the name and Docker IDs of our service containers.
  public $serviceContainers;

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
    // Data format: 'command [arguments]' or array('command [arguments]', 'command [arguments]')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $commands = is_array($commands) ? $commands : [$commands];


    if (!empty($commands)) {
      // Check that we have a container to execute on
      $configs = $this->getExecContainers();
      foreach ($configs as $type => $containers) {
        foreach ($containers as $container) {
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

            if ($this->checkCommandStatus($exec_command_exit_code) !==0) {
              return $exec_command_exit_code;
            }
          }
        }
      }
    }
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


  public function getExecContainers() {

    $configs = $this->executableContainers;
    foreach ($configs as $type => $containers) {
      foreach ($containers as $key => $container) {
        // Check if container is created.  If not, create it
        if (empty($container['created'])) {
          // TODO: This may be causing duplicate containers to be created
          // due to a race condition during short-running exec calls.
          $this->startContainer($container);
          $this->executableContainers[$type][$key] = $container;
        }
      }
    }
    return $this->executableContainers;
  }

  public function setExecContainers(array $containers) {
    $this->executableContainers = $containers;
  }

  protected function startContainer(&$container) {
    $manager = $this->docker->getContainerManager();
    // Get container configuration, which defines parameters such as exposed ports, etc.
    $configs = $this->getContainerConfiguration($container['image']);
    $config = $configs[$container['image']];
    // Add service container links
    $this->createContainerLinks($config);
    // Add volumes
    $this->createContainerVolumes($config);
    // Set a default CMD in case the container config does not set one.
    if (empty($config['Cmd'])) {
      $this->setDefaultCommand($config);
    }

    // Instantiate container
    $container_config = new ContainerConfig();
    $container_config->setImage($config['Image']);
    $container_config->setCmd($config['Cmd']);
    $host_config = new HostConfig();
    $host_config->setBinds($config['HostConfig']['Binds']);
    if (!empty($config['HostConfig']['Links'])) {
      $host_config->setLinks($config['HostConfig']['Links']);
    }
    $container_config->setHostConfig($host_config);
    $parameters = [];
    if (!empty($config['name'])) {
      $parameters = ['name' => $config['name']];
    }

    $create_result = $manager->create($container_config, $parameters);
    $container_id = $create_result->getId();

    // TODO: Ensure there are no stopped containers with the same name (currently throws fatal)
    $response = $manager->start($container_id);
    // TODO: Catch and exception if doesn't return 204.

    $service_container = $manager->find($container_id);
    $container['id'] = $service_container->getID();
    $container['name'] = $service_container->getName();
    $container['ip'] = $service_container->getNetworkSettings()->getIPAddress();
    $container['created'] = TRUE;
    $short_id = substr($container['id'], 0, 8);
    $this->io->writeln("<comment>Container <options=bold>${container['name']}</options=bold> created from image <options=bold>${container['image']}</options=bold> with ID <options=bold>$short_id</options=bold></comment>");
  }

  protected function setDefaultCommand(&$config) {
    $config['Cmd'] = ['/bin/bash', '-c', '/daemon.sh'];
  }

  protected function createContainerLinks(&$config) {
    $links = [];
    if (empty($this->serviceContainers)) {
      return;
    }
    $targets = $this->serviceContainers;
    foreach ($targets as $type => $containers) {
      foreach ($containers as $key => $container) {
        $config['HostConfig']['Links'][] = "${container['name']}:${container['name']}";
      }
    }
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

  public function getServiceContainers() {
    return $this->serviceContainers;
  }

  public function setServiceContainers(array $service_containers) {
    $this->serviceContainers = $service_containers;
  }

  public function startServiceContainerDaemons($container_type) {
    // $container_type is *always* 'db'
    $needs_sleep = FALSE;

    $manager = $this->docker->getContainerManager();
    $instances = [];

    $images = $manager->findAll();

    // Inexplicably loop through the data and reassign it to an array.
    foreach ($images as $running) {
      $running_container_name = explode(':', $running->getImage());
      $id = substr($running->getID(), 0, 8);
      $instances[$running_container_name[0]] = $id;
    };
    foreach ($this->serviceContainers[$container_type] as $key => $image) {
      // look for the 'service container' that we want to start.
      if (in_array($image['image'], array_keys($instances))) {
        // TODO: Determine service container ports, id, etc, and save it to the build.
        $this->io->writeln("<comment>Found existing <options=bold>${image['image']}</options=bold> service container instance.</comment>");
        // TODO: Load up container parameters
        $container = $manager->find($instances[$image['image']]);
        $container_id = $container->getID();
        $container_name = $container->getName();
        $container_ip = $container->getNetworkSettings()->getIPAddress();
        $this->serviceContainers[$container_type][$key]['id'] = $container_id;
        $this->serviceContainers[$container_type][$key]['name'] = $container_name;
        $this->serviceContainers[$container_type][$key]['ip'] = $container_ip;
        continue;
      }
      // Container not running, so we'll need to create it.
      $this->io->writeln("<comment>No active <options=bold>${image['image']}</options=bold> service container instances found. Generating new service container.</comment>");

      // Get container configuration, which defines parameters such as exposed ports, etc.
      $configs = $this->getContainerConfiguration($image['image']);
      $config = $configs[$image['image']];
      // TODO: Allow classes to modify the default configuration before processing
      // Instantiate container

      // TODO: Use a normalizer
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

      // Create the docker container instance, running as a daemon.
      // TODO: Ensure there are no stopped containers with the same name (currently throws fatal)
      $response = $manager->start($container_id);
      // TODO: Catch and exception if doesn't return 204.

      $container = $manager->find($container_id);

      $container_id = $container->getID();
      $container_name = $container->getName();
      $container_ip = $container->getNetworkSettings()->getIPAddress();

      $this->serviceContainers[$container_type][$key]['id'] = $container_id;
      $this->serviceContainers[$container_type][$key]['name'] = $container_name;
      $this->serviceContainers[$container_type][$key]['ip'] = $container_ip;
      $short_id = substr($container_id, 0, 8);
      $this->io->writeln("<comment>Created new <options=bold>${image['image']}</options> container instance with ID <options=bold>$short_id</options=bold></comment>");
    }
    // @TODO: should probably add the container environment as a service
    $this->database->setHost($container_ip);
    // @TODO: all of this should probably live inside of the database
    $this->database->connect();

  }

  public function validateImageNames($containers) {
    // Verify that the appropriate container images exist
    $this->io->writeln("<comment>Validating container images exist</comment>");

    $manager = $this->docker->getImageManager();
    foreach ($containers as $key => $image_name) {
      $container_string = explode(':', $image_name['image']);
      $name = $container_string[0];

      try {
        $image = $manager->find($name);
      }
      catch (ClientErrorException $e) {
        $this->io->drupalCIError("Missing Image", "Required container image <options=bold>'$name'</options=bold> not found.");
        return FALSE;
      }
      $id = substr($image->getID (), 0, 8);
      $this->io->writeln("<comment>Found image <options=bold>$name/options=bold> with ID <options=bold>$id</options=bold></comment>");
    }
    return TRUE;
  }


}
