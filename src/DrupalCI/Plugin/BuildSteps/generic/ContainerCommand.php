<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\generic\Command
 *
 * Processes "[build_step]: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\BuildSteps\generic;

use DrupalCI\Console\Output;
use DrupalCI\Plugin\JobTypes\JobInterface;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("command")
 */
class ContainerCommand extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run(JobInterface $job, $data) {
    $output = $this->container['console.output'];

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
          $output->writeLn("<info>Executing on container instance $short_id:</info>");
          foreach ($data as $cmd) {
            $output->writeLn("<fg=magenta>$cmd</fg=magenta>");
            $exec = ["/bin/bash", "-c", $cmd];
            $exec_id = $manager->exec($instance, $exec, TRUE, TRUE, TRUE, TRUE);
            $output->writeLn("<info>Command created as exec id " . substr($exec_id, 0, 8) . "</info>");
            $result = $manager->execstart($exec_id, function ($result, $type) use ($output) {
              if ($type === 1) {
                $output->write("$result");
              }
              else {
                Output::error('Error', $result, $output);
              }
            });
            // Response stream is never read you need to simulate a wait in order to get output
            $result->getBody()->getContents();
            $output->writeLn((string) $result);
            $inspection = $manager->execinspect($exec_id);

            if ($this->checkCommandStatus($inspection->ExitCode) !==0) {
              $job->error();
              break 3;
            }
          }
        }
      }
    }
  }

  protected function checkCommandStatus($signal) {
    if ($signal !==0) {
      Output::error('Error', "Received a non-zero return code from the last command executed on the container.  (Return status: " . $signal . ")", $this->container['console.output']);
      return 1;
    }
    else {
      return 0;
    }
  }
}
