<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\environment\DbEnvironment
 *
 * Processes "environment: db:" parameters from within a build definition,
 * ensures appropriate Docker container images exist, and launches any new
 * database service containers as required.
 */

namespace DrupalCI\Plugin\BuildSteps\environment;

use DrupalCI\Console\Output;
use DrupalCI\Build\BuildInterface;

/**
 * @PluginID("db")
 */
class DbEnvironment extends EnvironmentBase {

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, $data) {
    // We don't need to initialize any service container for SQLite.
    if (strpos($build->getBuildVar('DCI_DBVersion'), 'sqlite') === 0) {
      return;
    }

    // Data format: 'mysql-5.5' or array('mysql-5.5', 'pgsql-9.1')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $data = is_array($data) ? $data : [$data];
    Output::writeLn("<info>Parsing required database container image names ...</info>");
    $containers = $this->buildImageNames($data, $build);
    if ($valid = $this->validateImageNames($containers, $build)) {
      $service_containers = $build->getServiceContainers();
      $service_containers['db'] = $containers;
      $build->setServiceContainers($service_containers);
      $build->startServiceContainerDaemons('db');
    }
  }

  public function buildImageNames($data, BuildInterface $build) {
    $images = [];
    foreach ($data as $key => $db_version) {
      $images["$db_version"]['image'] = "drupalci/$db_version";
      Output::writeLn("<comment>Adding image: <options=bold>drupalci/$db_version</options=bold></comment>");
    }
    return $images;
  }

}
