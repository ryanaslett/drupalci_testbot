<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\environment\PhpEnvironment
 *
 * Processes "environment: php:" parameters from within a job definition,
 * ensures appropriate Docker container images exist, and defines the
 * appropriate execution container for communication back to JobBase.
 */

namespace DrupalCI\Plugin\BuildSteps\environment;

use DrupalCI\Console\Output;
use DrupalCI\Plugin\JobTypes\JobInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @PluginID("php")
 */
class PhpEnvironment extends EnvironmentBase {

  /**
   *
   * @param JobInterface $job
   * @param type $data
   */
  public function run(JobInterface $job, $data) {
    // Data format: '5.5' or array('5.4', '5.5')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $data = is_array($data) ? $data : [$data];
    $this->output->writeLn("<info>Parsing required PHP container image names ...</info>");
    $containers = $job->getExecContainers();
    $containers['php'] = $this->buildImageNames($data, $job);
    $valid = $this->validateImageNames($containers['php'], $job);
    if (!empty($valid)) {
      $job->setExecContainers($containers);
      // Actual creation and configuration of the executable containers occurs
      // in the getExecContainers() method call.
    }
  }

  /**
   *
   * @param array $data
   * @param JobInterface $job
   *
   * @return array
   *   List of Docker images.
   */
  protected function buildImageNames($data, JobInterface $job) {
    $images = [];
    foreach ($data as $key => $php_version) {
      $images["php-$php_version"]['image'] = "drupalci/php-$php_version";
      $this->output->writeLn("<comment>Adding image: <options=bold>drupalci/php-$php_version</options=bold></comment>");
    }
    return $images;
  }
}
