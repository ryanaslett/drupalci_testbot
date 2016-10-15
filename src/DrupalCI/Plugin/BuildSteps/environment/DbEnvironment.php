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
use DrupalCI\Plugin\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskTrait;

/**
 * Starts a service container daemon for the specified database type.
 *
 * @PluginID("db")
 */
class DbEnvironment extends EnvironmentBase implements BuildTaskInterface {

  use BuildTaskTrait;

  public function getDefaultConfiguration() {
    return [
      'DCI_DBType' => 'mysql',
      'DCI_DBVersion' => '5.5',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, &$config) {

    $config = $this->resolveDciVariables($config);

    // We don't need to initialize any service container for SQLite.
    if (strpos($config['type'], 'sqlite') === 0) {
      return;
    }

    Output::writeLn("<info>Parsing required database container image names ...</info>");
    $containers = $this->buildImageNames($config, $build);
    if ($valid = $this->validateImageNames($containers, $build)) {
      // @todo Move the housekeeping to the build instead of doing it here.
      $service_containers = $build->getServiceContainers();
      $service_containers['db'] = $containers;
      $build->setServiceContainers($service_containers);
      $build->startServiceContainerDaemons('db');
    }
  }

  public function buildImageNames($config, BuildInterface $build) {
    $db_version = $config['type'] . '-' . $config['version'];
    $images["$db_version"]['image'] = "drupalci/$db_version";
    Output::writeLn("<comment>Adding image: <options=bold>drupalci/$db_version</options=bold></comment>");
    return $images;
  }

}
