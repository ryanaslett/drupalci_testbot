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

use DrupalCI\Build\Environment\DatabaseInterface;
use DrupalCI\Console\Output;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskTrait;
use DrupalCI\Injectable;
use Pimple\Container;

/**
 * Starts a service container daemon for the specified database type.
 *
 * @PluginID("db")
 */
class DbEnvironment extends EnvironmentBase implements BuildTaskInterface, Injectable {

  use BuildTaskTrait;

  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    parent::inject($container);
    $this->buildVars = $container['build.vars'];
    /* @var \DrupalCI\Build\Environment\DatabaseInterface */
    $this->database = $container['db.system'];
    /* @var \DrupalCI\Build\Environment\DatabaseInterface */
    // @TODO move this to the simpletest execution class
    $this->results_database = $container['db.results'];

    $this->build_definition = $container['build.definition'];
  }

  public function getDefaultConfiguration() {
    return [
      'DCI_DBType' => 'mysql',
      'DCI_DBVersion' => '5.5',
    ];
  }


  /**
   * @var \DrupalCI\Build\Environment\DatabaseInterface
   */
  protected $database;

  /**
   * @var \DrupalCI\Build\Environment\DatabaseInterface
   *
   * @TODO: Remove this. The results database should be established as
   * part of the RunTests execute task when it exists. For now, we'll
   * fake it here until such time as we have the right place for it.
   */
  protected $results_database;

  /**
   * @var \DrupalCI\Build\Definition\BuildDefinition
   */
  protected $build_definition;


  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, &$config) {
    $this->setUpDatabase();

    // @TODO get rid of this. and move it to where a results db actually
    // gets created and needed, in RunTests task
    $majver = $build->getCodebase()->getCoreMajorVersion();
    if($majver > 7) {
      $this->setUpResultsDB($build);
    }

    $config = $this->resolveDciVariables($config);

    // We don't need to initialize any service container for SQLite.
    if (strpos($config['type'], 'sqlite') === 0) {
      return;
    }
    // OPUT
    $this->io->writeLn("<info>Parsing required database container image names ...</info>");
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
    // OPUT
    $this->io->writeLn("<comment>Adding image: <options=bold>drupalci/$db_version</options=bold></comment>");
    return $images;
  }

  /*
   * This needs to actually implement something on the interface. Not sure what
   * That is supposed to be at this point. Right now its just
   * Move all the preprocessing logic to here.
   */
  public function setUpDatabase() {
     // BUILDVARS
      $this->setDBName($this->build_definition->getDCIVariable('DCI_BuildId'));
      $this->setDBVersion($this->build_definition->getDCIVariable('DCI_DBType'));
      $this->setPassword($this->build_definition->getDCIVariable('DCI_DBPassword'));
      $this->setUser($this->build_definition->getDCIVariable('DCI_DBUser'));

  }

  public function setPassword($password) {
    $this->database->setPassword($password);
  }

  public function setUser($username) {
    $this->database->setUsername($username);
  }

  /**
   * {@inheritdoc}
   */

  public function setDBVersion($source_value) {
    $mod_value = explode(':', $source_value, 2)[0];
    $dbtype = explode('-', $mod_value, 2)[0];
    $host_part = str_replace([':', '.'], '-', $source_value);
    $host = 'drupaltestbot-db-' . $host_part;
    $this->database->setDbType($dbtype);
    $this->database->setHost($host);
  }

  /**
   * {@inheritdoc}
   */

  public function setDBName($db_name) {
    $db_name = str_replace('-', '_', $db_name);
    $db_name = preg_replace('/[^0-9_A-Za-z]/', '', $db_name);
    $this->database->setDbname($db_name);
  }

  public function setupResultsDB(BuildInterface $build) {

    $source_dir = $build->getCodebase()->getWorkingDir();
    $dbfile = $source_dir . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . basename($this->build_definition->getDCIVariable('DCI_SQLite'));
    $this->results_database->setDBFile($dbfile);
    $this->results_database->setDbType('sqlite');
  }

}
