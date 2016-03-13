<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\environment\DbEnvironment
 *
 * Processes "environment: db:" parameters from within a job definition,
 * ensures appropriate Docker container images exist, and launches any new
 * database service containers as required.
 */

namespace DrupalCI\Plugin\BuildSteps\environment;

use DrupalCI\Plugin\JobTypes\JobInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @PluginID("db")
 */
class DbEnvironment extends EnvironmentBase {

  /**
   * {@inheritdoc}
   */
  public function run(JobInterface $job, $data) {
    $output = $this->container['console.output'];
    // We don't need to initialize any service container for SQLite.
    if (strpos($job->getBuildVar('DCI_DBVersion'), 'sqlite') === 0) {
      return;
    }

    // Data format: 'mysql-5.5' or array('mysql-5.5', 'pgsql-9.1')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $data = is_array($data) ? $data : [$data];
    $output->writeLn("<info>Parsing required database container image names ...</info>");
    $containers = $this->buildImageNames($data, $job, $output);
    if ($valid = $this->validateImageNames($containers, $job, $output)) {
      $service_containers = $job->getServiceContainers();
      $service_containers['db'] = $containers;
      $job->setServiceContainers($service_containers);
      $job->startServiceContainerDaemons('db');
    }
  }

  public function buildImageNames($data, JobInterface $job, OutputInterface $output) {
    $images = [];
    foreach ($data as $key => $db_version) {
      $images["$db_version"]['image'] = "drupalci/$db_version";
      $output->writeLn("<comment>Adding image: <options=bold>drupalci/$db_version</options=bold></comment>");
    }
    return $images;
  }

}
