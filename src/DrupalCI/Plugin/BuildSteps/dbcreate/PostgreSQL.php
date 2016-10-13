<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\dbcreate\PostgreSQL.
 */

namespace DrupalCI\Plugin\BuildSteps\dbcreate;

use DrupalCI\Plugin\BuildSteps\generic\ContainerCommand;
use DrupalCI\Build\BuildInterface;

/**
 * @PluginID("pgsql")
 */
class PostgreSQL extends ContainerCommand {

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, $data) {

    $parts = parse_url($build->getBuildVar('DCI_DBUrl'));
    $host = $parts['host'];
    $user = $parts['user'];
    $pass = $parts['pass'];
    $db_name = $data ?: ltrim($parts['path'], '/');

    // Create role, database, and schema for PostgreSQL commands.
    $createdb = "PGPASSWORD=$pass PGUSER=$user createdb -E 'UTF-8' -O $user -h $host $db_name";

    parent::run($build, $createdb);
  }
}
