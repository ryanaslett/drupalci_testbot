<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\dbcreate\MariaDB.
 */

namespace DrupalCI\Plugin\BuildSteps\dbcreate;

use DrupalCI\Plugin\BuildSteps\generic\ContainerCommand;
use DrupalCI\Build\BuildInterface;

/**
 * @PluginID("mariadb")
 */
class MariaDB extends ContainerCommand {
//DBX Get
  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, $data) {
    $parts = parse_url($build->getBuildVar('DCI_DBUrl'));
    $parts['scheme'] = 'mysql';
    $host = $parts['host'];
    $user = $parts['user'];
    $pass = $parts['pass'];
    $db_name = $data ?: ltrim($parts['path'], '/');
    $cmd = "mysql -h $host -u $user -p$pass -e 'CREATE DATABASE $db_name'";
    parent::run($build, $cmd);
  }
}
