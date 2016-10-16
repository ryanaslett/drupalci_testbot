<?php

namespace DrupalCI\Plugin\BuildSteps\install;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Console\Output;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use Pimple\Container;

/**
 * @PluginID("dbcreate")
 *
 * @todo Figure out db type from dburl.
 * @todo validate dbtype/dburl from the other one, whichever makes more sense.
 */
class DbCreate extends PluginBase implements BuildTaskInterface, Injectable {

  use BuildTaskTrait;

  /**
   * The BuildSteps plugin manager.
   *
   * We use this to create command plugins.
   *
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildStepPluginManager;

  /**
   * {@inheritdoc}
   */
  public function setContainer(Container $container) {
    $this->buildStepPluginManager = $container['plugin.manager.factory']->create('BuildSteps');
    $this->buildVars = $container['build.vars'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return [
      'DCI_DBType' => '',
      'DCI_DBUrl' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $job, &$config) {
    $config = $this->resolveDciVariables($config);

    switch ($config['type']) {
      case 'mariadb':
        $this->createDbMaria($job, $config['url']);
        break;
      case 'mysql':
        $this->createDbMysql($job, $config['url']);
        break;
      case 'pgsql':
        $this->createDbPostgre($job, $config['url']);
        break;
      case 'sqlite':
        Output::writeLn('SQLite is the destination database. No creation performed.');
        // No db creation is needed for SQLite.
        break;

      default:
        // @todo Use a better exception type.
        throw new \Exception('No valid DB type was specified. Can not create a fixture database.');
    }
  }

  protected function createDbMysql(BuildInterface $job, $db_url) {
    $parts = parse_url($db_url);
    $host = $parts['host'];
    $user = $parts['user'];
    $pass = $parts['pass'];
    $db_name = ltrim($parts['path'], '/');

    $cmd = "mysql -h $host -u $user -p$pass -e 'CREATE DATABASE $db_name'";
    $command = $this->buildStepPluginManager->getPlugin('generic', 'command', [$cmd]);
    $command->run($job, $cmd);
  }

  protected function createDbMaria(BuildInterface $job, $db_url) {
    $parts = parse_url($db_url);
    $parts['scheme'] = 'mysql';
    $host = $parts['host'];
    $user = $parts['user'];
    $pass = $parts['pass'];
    $db_name = ltrim($parts['path'], '/');

    $cmd = "mysql -h $host -u $user -p$pass -e 'CREATE DATABASE $db_name'";
    $command = $this->buildStepPluginManager->getPlugin('generic', 'command', [$cmd]);
    $command->run($job, $cmd);
  }

  protected function createDbPostgre(BuildInterface $job, $db_url) {
    $parts = parse_url($db_url);
    $host = $parts['host'];
    $user = $parts['user'];
    $pass = $parts['pass'];
    $db_name = ltrim($parts['path'], '/');

    // Create role, database, and schema for PostgreSQL commands.
    $cmd = "PGPASSWORD=$pass PGUSER=$user createdb -E 'UTF-8' -O $user -h $host $db_name";
    $command = $this->buildStepPluginManager->getPlugin('generic', 'command', [$cmd]);
    $command->run($job, $cmd);
  }

}
