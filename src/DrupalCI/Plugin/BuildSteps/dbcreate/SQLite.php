<?php

/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\dbcreate\SQLite.
 */

namespace DrupalCI\Plugin\BuildSteps\dbcreate;

use DrupalCI\Plugin\BuildSteps\generic\ContainerCommand;
use DrupalCI\Build\BuildInterface;

/**
 * @PluginID("sqlite")
 */
class SQLite extends ContainerCommand {

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $job, $data) {
    // Nothing to do here, the SQLite database file will be created by the test
    // runner.
  }

}
