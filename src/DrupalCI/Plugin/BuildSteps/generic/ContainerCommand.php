<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\generic\Command
 *
 * Processes "[build_step]: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\BuildSteps\generic;

use DrupalCI\Console\Output;
use DrupalCI\Plugin\BuildSteps\BuildStepBase;
use DrupalCI\Plugin\JobTypes\JobInterface;

/**
 * @PluginID("command")
 */
class ContainerCommand extends BuildStepBase {

  /**
   * @var integer
   *
   * Store the exit status for the command.
   */
  protected $exit_code;

  protected function setExitCode($exit_code) {
    $this->exit_code = $exit_code;
  }
  public function getExitCode() {
    return $this->exit_code;
  }

  /**
   * {@inheritdoc}
   */
  public function run(JobInterface $job, $data) {
    // Data format: 'command [arguments]' or array('command [arguments]', 'command [arguments]')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $data = is_array($data) ? $data : [$data];
    $docker = $job->getDocker();
    $manager = $docker->getContainerManager();

    if (!empty($data)) {
      // Check that we have a container to execute on
      $configs = $job->getExecContainers();
      foreach ($configs as $type => $containers) {
        foreach ($containers as $container) {
          $id = $container['id'];
          $instance = $manager->find($id);
          $short_id = substr($id, 0, 8);
          Output::writeLn("<info>Executing on container instance $short_id:</info>");
          foreach ($data as $cmd) {
            Output::writeLn("<fg=magenta>$cmd</fg=magenta>");
            $exec = ["/bin/bash", "-c", $cmd];
            $exec_id = $manager->exec($instance, $exec, TRUE, TRUE, TRUE, TRUE);
            Output::writeLn("<info>Command created as exec id " . substr($exec_id, 0, 8) . "</info>");
            $result = $manager->execstart($exec_id, function ($result, $type) {
              if ($type === 1) {
                Output::write("$result");
              }
              else {
                Output::error('Error', $result);
                $this->update("Error", "SystemError", "Unable to execute command on container.");
              }
            });
            // Response stream is never read you need to simulate a wait in order to get output
            $result->getBody()->getContents();
            Output::writeLn((string) $result);
            $inspection = $manager->execinspect($exec_id);
            $this->setExitCode($inspection->ExitCode);
            Output::writeLn("Command Exit Code: " . $this->getExitCode());

            if ($this->checkCommandStatus($inspection->ExitCode) !==0) {
              $job->error();
              break 3;
            }
          }
        }
      }
      // If no errors encountered, assume passed.
      if ($this->getState() != "Error") {
        $this->update("Completed", "Pass", "Command(s) executed on container.");
      }
    }
    else {
      $this->update("Completed", "Warning", "No command passed to build step for execution");
      return;
    }
  }

  protected function checkCommandStatus($signal) {
    if ($signal !==0) {
      Output::error('Error', "Received a non-zero return code from the last command executed on the container.  (Return status: " . $signal . ")");
      $this->update("Completed", "Error", "Received a non-zero exit code from last command executed on the container.  (Return status: $signal )");
      return 1;
    }
    else {
      return 0;
    }
  }
}
