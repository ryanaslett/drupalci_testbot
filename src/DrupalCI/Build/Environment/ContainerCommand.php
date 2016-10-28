<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\generic\Command
 *
 * Processes "[build_step]: command:" instructions from within a build definition.
 */

namespace DrupalCI\Build\Environment;

use Docker\API\Model\ExecConfig;
use Docker\API\Model\ExecStartConfig;
use Docker\Manager\ExecManager;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\PluginBase;
use Pimple\Container;


class ContainerCommand implements Injectable {

  /**
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  public function inject(Container $container) {

    $this->io = $container['console.io'];
  }

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, $data) {
    // Data format: 'command [arguments]' or array('command [arguments]', 'command [arguments]')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $data = is_array($data) ? $data : [$data];
    // DOCKER
    $docker = $build->getDocker();
    $manager = $docker->getContainerManager();

    if (!empty($data)) {
      // Check that we have a container to execute on
      $configs = $build->getExecContainers();
      foreach ($configs as $type => $containers) {
        foreach ($containers as $container) {
          $id = $container['id'];
          $short_id = substr($id, 0, 8);
          $this->io->writeLn("<info>Executing on container instance $short_id:</info>");
          foreach ($data as $cmd) {
            $this->io->writeLn("<fg=magenta>$cmd</fg=magenta>");

            $exec_config = new ExecConfig();
            $exec_config->setTty(FALSE);
            $exec_config->setAttachStderr(TRUE);
            $exec_config->setAttachStdout(TRUE);
            $exec_config->setAttachStdin(FALSE);
            $command = ["/bin/bash", "-c", $cmd];
            $exec_config->setCmd($command);

            $exec_manager = $docker->getExecManager();
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
}
