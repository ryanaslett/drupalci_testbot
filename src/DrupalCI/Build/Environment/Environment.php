<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\generic\Command
 *
 * Processes "[build_step]: command:" instructions from within a build definition.
 */

namespace DrupalCI\Build\Environment;

use Docker\API\Model\ContainerConfig;
use Docker\API\Model\CreateImageInfo;
use Docker\API\Model\ExecConfig;
use Docker\API\Model\ExecStartConfig;
use Docker\API\Model\HostConfig;
use Docker\Manager\ExecManager;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Plugin\PluginBase;
use Http\Client\Common\Exception\ClientErrorException;
use Pimple\Container;
use Symfony\Component\Console\Helper\ProgressBar;
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
  protected $databaseContainer;

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
  public function executeCommands($commands, $container_id = '') {
    // @TODO someday we may have more than one container. This currently assumes
    // just the single Exec container.

    // Data format: 'command [arguments]' or array('command [arguments]', 'command [arguments]')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $commands = is_array($commands) ? $commands : [$commands];


    if (!empty($commands)) {
      if (!empty($container_id)){
        $id = $container_id;
      } else {
        $container = $this->getExecContainer();
        $id = $container['id'];
      }

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
    if ($signal !== 0) {
      $this->io->drupalCIError('Error', "Received a non-zero return code from the last command executed on the container.  (Return status: " . $signal . ")");
      return 1;
    }
    else {
      return 0;
    }
  }
  /**
   * @return mixed
   */
  public function getDatabaseContainer() {
    return $this->databaseContainer;
  }

  public function getExecContainer() {
    return $this->executableContainer;
  }

  /**
   * @param array $container
   */
  public function startExecContainer($container) {

    // Map working directory
    $container['HostConfig']['Binds'][] = $this->build->getSourceDirectory() . ':/var/www/html';
    $container['HostConfig']['Binds'][] = "/var/lib/drupalci/docker-tmp:/tmp";
    $this->executableContainer = $this->startContainer($container);

  }


  public function startServiceContainerDaemons($db_container) {
    if (strpos($this->database->getDbType(), 'sqlite') === 0) {
      return;
    }
    $db_container['HostConfig']['Binds'][0] = $this->build->getDBDirectory() . ':' . $this->database->getDataDir();



    $this->databaseContainer = $this->startContainer($db_container);
    $this->database->setHost($this->databaseContainer['ip']);


  }

  public function terminateContainers() {

    $manager = $this->docker->getContainerManager();

    // Kill the containers we started.
    $manager->remove($this->executableContainer['id'], ['force' => TRUE]);

    if ($this->database->getDbType() !== 'sqlite') {
      $manager->remove($this->databaseContainer['id'],['force' => TRUE]);
    }
  }

  protected function validateImageName($image_name) {
    // Verify that the appropriate container images exist
    $this->io->writeln("<comment>Validating container images exist</comment>");

    $manager = $this->docker->getImageManager();

    $name = $image_name['Image'];
    try {
      $image = $manager->find($name);
      $id = substr($image->getID(), 0, 8);
      $this->io->writeln("<comment>Found image <options=bold>$name/options=bold> with ID <options=bold>$id</options=bold></comment>");
    }
    catch (ClientErrorException $e) {
      // @TODO this is where we go ahead and pull the image if it doesnt exist.
      $this->io->drupalCIError("Missing Image", "Required container image <options=bold>'$name'</options=bold> not found.");
      $this->pull($name);
    }


    return TRUE;
  }

  protected function startContainer($config) {

    $valid = $this->validateImageName($config);
    if (!empty($valid)) {

      $manager = $this->docker->getContainerManager();
      $container_config = new ContainerConfig();
      $container_config->setImage($config['Image']);
      $host_config = new HostConfig();
      $host_config->setBinds($config['HostConfig']['Binds']);
      $container_config->setHostConfig($host_config);
      $parameters = [];
      $create_result = $manager->create($container_config, $parameters);
      $container_id = $create_result->getId();

      $response = $manager->start($container_id);
      // TODO: Catch and exception if doesn't return 204.

      $executable_container = $manager->find($container_id);

      $container['id'] = $executable_container->getID();
      $container['name'] = $executable_container->getName();
      $container['ip'] = $executable_container->getNetworkSettings()
        ->getIPAddress();
      $container['image'] = $config['Image'];

      $short_id = substr($container['id'], 0, 8);
      $this->io->writeln("<comment>Container <options=bold>${container['name']}</options=bold> created from image <options=bold>${config['Image']}</options=bold> with ID <options=bold>$short_id</options=bold></comment>");
      return $container;
    }
    else {
      throw new BuildTaskException("Starting Container Failed");
    }
  }

  /**
   * (#inheritdoc)
   *
   * @param $name
   */
  protected function pull($name) {
    $manager = $this->docker->getImageManager();
    $progressInformation = null;
    $response = $manager->create('', ['fromImage' => $name . ':latest'],  $manager::FETCH_STREAM);

    //$response->onFrame(function (CreateImageInfo $createImageInfo) use (&$progressInformation) {
    $response->onFrame(function (CreateImageInfo $createImageInfo) use (&$progressInformation) {
      $createImageInfoList[] = $createImageInfo;
      if ($createImageInfo->getStatus() === "Downloading") {
        $progress = $createImageInfo->getProgress();
        preg_match("/\]\s+(?P<current>(?:[0-9\.]+)?)\s[kM]*B\/(?P<total>(?:[0-9\.]+)?)\s/",$progress,$status);
        // OPUT
//        $progressbar = new ProgressBar($this->io, $status['total']);
//        $progressbar->start();
//        $progressbar->advance($status['current']);
      } else {
        $this->io->writeln("<comment>" . $createImageInfo->getStatus() . "</comment>");
      }
    });
    $response->wait();

    $this->io->writeln("");
  }
}
