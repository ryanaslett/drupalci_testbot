<?php
/**
 * @file
 * Base Build class for DrupalCI.
 */

namespace DrupalCI\Build;

use Docker\API\Model\ContainerConfig;
use Docker\API\Model\HostConfig;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Console\Output;
use DrupalCI\Injectable;
use DrupalCI\InjectableTrait;
use DrupalCI\Build\Codebase\CodeBase;
use DrupalCI\Build\Definition\BuildDefinition;
use DrupalCI\Build\Results\BuildResults;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tests\Output\ConsoleOutputTest;
use Symfony\Component\Process\Process;
use Docker\Docker;
use Docker\DockerClient as Client;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Pimple\Container;
use PDO;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Yaml\Yaml;

class Build implements BuildInterface, Injectable {

  /**
   * @var \Pimple\Container
   */
  protected $container;

  /**
  /**
   * @var \Symfony\Component\Yaml\Parser
   *
   *   Parsed Yaml of the build definition.
   */
  protected $yamlparser;


  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    $this->container = $container;
    $this->buildVars = $container['build.vars'];
    $this->yamlparser = $container['yaml.parser'];
    $this->buildTaskPluginManager = $this->container['plugin.manager.factory']->create('BuildTask');
  }

  /**
   * Stores the build type
   *
   * @var string
   */
  protected $buildType = 'base';

  public function getBuildType() {
    return $this->buildType;
  }


  /**
   * Stores the calling command's output buffer
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public $output;

  public function setOutput(OutputInterface $output) {
    $this->output = $output;
  }

  public function getOutput() {
    return $this->output;
  }

  /**
   * Stores a build ID for this build
   *
   * @var string
   */
  protected $buildId;

  public function getBuildId() {
    return $this->buildId;
  }

  public function setBuildId($buildId) {
    $this->buildId = $buildId;
  }

  /**
   * Stores the pift-ci-job id for this build.
   *
   * @var string
   */
  protected $drupalOrgBuildId;

  public function getDrupalOrgBuildId() {
    return $this->drupalOrgBuildId;
  }

  public function setDrupalOrgBuildId($drupalOrgBuildId) {
    $this->drupalOrgBuildId = $drupalOrgBuildId;
  }

  /**
   * Stores the jenkins build id for this build.
   *
   * @var string
   */
  protected $jenkinsBuildId;

  public function getJenkinsBuildId() {
    return $this->jenkinsBuildId;
  }

  public function setJenksinBuildId($jenkinsBuildId) {
    $this->jenkinsBuildId = $jenkinsBuildId;
  }


  /**
   * Stores the build definition object for this build
   *
   * @var \DrupalCI\Build\Definition\BuildDefinition
   */
  protected $buildDefinition = NULL;
  public function getBuildDefinition() {  return $this->buildDefinition;  }
  public function setBuildDefinition(BuildDefinition $build_definition) {
    $build_definition->inject($this->container);
    $this->buildDefinition = $build_definition;

  /**
   * Given a file, returns an array containing the parsed YAML contents from that file
   *
   * @param $source
   *   A YAML source file
   *
   * @return array
   *   an array containing the parsed YAML contents from the source file
   * @throws ParseException
   */
  protected function loadYaml($source) {
    if ($content = file_get_contents($source)) {
      return $this->yamlparser->parse($content);
    }
    throw new ParseException("Unable to parse build definition file at $source.");
  }

  /**
   * Stores the codebase object for this build
   *
   * @var \DrupalCI\Build\Codebase\CodeBase
   */
  // CODEBASE
  protected $codeBase;

  public function getCodebase() {
    return $this->codeBase;
  }

  public function setCodebase(CodeBase $codeBase) {
    $this->codeBase = $codeBase;
  }

  /**
   * Stores the results object for this build
   *
   * @var \DrupalCI\Build\Results\BuildResults
   */
  protected $buildResults;

  public function getBuildResults() {
    return $this->buildResults;
  }

  public function setBuildResults(BuildResults $build_results) {
    $this->buildResults = $build_results;
  }

  /**
   * Defines argument variable names which are valid for this build type
   *
   * @var array
   */
  protected $availableArguments = [];

  public function getAvailableArguments() {
    return $this->availableArguments;
  }

  /**
   * Defines the default arguments which are valid for this build type
   *
   * @var array
   */
  protected $defaultArguments = [];

  public function getDefaultArguments() {
    return $this->defaultArguments;
  }

  /**
   * Defines the required arguments which are necessary for this build type
   *
   * Format:  array('ENV_VARIABLE_NAME' => 'CONFIG_FILE_LOCATION'), where
   * CONFIG_FILE_LOCATION is a colon-separated nested location for the
   * equivalent variable in a build definition file.
   *
   * @var array
   */
  protected $requiredArguments = [];

  public function getRequiredArguments() {
    return $this->requiredArguments;
  }

  /**
   * Defines initial platform defaults for all builds (if not overridden).
   *
   * @var array
   */
  protected $platformDefaults = [
    "DCI_CoreProject" => "Drupal",
  ];

  public function getPlatformDefaults() {
    return $this->platformDefaults;
  }

  /**
   * The build variables service.
   *
   * @var \DrupalCI\Build\BuildVariablesInterface
   */
  protected $buildVars;

  public function getBuildVars() {
    return $this->buildVars->getAll();
  }

  public function setBuildVars(array $build_vars) {
    return $this->buildVars->setAll($build_vars);
  }

  public function getBuildVar($build_var) {
    return $this->buildVars->get($build_var, NULL);
  }

  public function setBuildVar($build_var, $value) {
    return $this->buildVars->set($build_var, $value);
  }

  /**
   * Stores our Docker Container manager
   *
   * @var \Docker\Docker
   */
  protected $docker;

  /**
   * @return \Docker\Docker
   */
  // DOCKER
  public function getDocker() {
    $client = Client::createFromEnv();
    if (NULL === $this->docker) {
      $this->docker = new Docker($client);
    }
    return $this->docker;
  }

  /**
   * @var array
   */
  protected $pluginDefinitions;

  /**
   * @var array
   */
  protected $plugins;

  // Holds the name and Docker IDs of our service containers.
  public $serviceContainers;

  // Holds the name and Docker IDs of our executable containers.
  public $executableContainers = [];

  public function getServiceContainers() {
    // DOCKER
    return $this->serviceContainers;
  }

  public function setServiceContainers(array $service_containers) {
    // DOCKER
    $this->serviceContainers = $service_containers;
  }

  public function error() {
    $results = $this->getBuildResults();
    $stage = $results->getCurrentStage();
    $step = $results->getCurrentStep();
    $results->setResultByStage($stage, 'Error');
    $results->setResultByStep($stage, $step, 'Error');
  }

  public function fail() {
    $results = $this->getBuildResults();
    $stage = $results->getCurrentStage();
    $step = $results->getCurrentStep();
    $results->setResultByStage($stage, 'Fail');
    $results->setResultByStep($stage, $step, 'Fail');
  }

  public function getExecContainers() {
    // DOCKER
    $configs = $this->executableContainers;
    foreach ($configs as $type => $containers) {
      foreach ($containers as $key => $container) {
        // Check if container is created.  If not, create it
        if (empty($container['created'])) {
          // TODO: This may be causing duplicate containers to be created
          // due to a race condition during short-running exec calls.
          $this->startContainer($container);
          $this->executableContainers[$type][$key] = $container;
        }
      }
    }
    return $this->executableContainers;
  }

  public function setExecContainers(array $containers) {
    // DOCKER
    $this->executableContainers = $containers;
  }

  public function startContainer(&$container) {
    // DOCKER
    $docker = $this->getDocker();
    $manager = $docker->getContainerManager();
    // Get container configuration, which defines parameters such as exposed ports, etc.
    $configs = $this->getContainerConfiguration($container['image']);
    $config = $configs[$container['image']];
    // TODO: Allow classes to modify the default configuration before processing
    // Add service container links
    $this->createContainerLinks($config);
    // Add volumes
    $this->createContainerVolumes($config);
    // Set a default CMD in case the container config does not set one.
    if (empty($config['Cmd'])) {
      $this->setDefaultCommand($config);
    }

    // Instantiate container
    // TODO: Use a normalizer
    $container_config = new ContainerConfig();
    $container_config->setImage($config['Image']);
    $container_config->setCmd($config['Cmd']);
    $host_config = new HostConfig();
    $host_config->setBinds($config['HostConfig']['Binds']);
    if (!empty($config['HostConfig']['Links'])) {
      $host_config->setLinks($config['HostConfig']['Links']);
    }
    $container_config->setHostConfig($host_config);
    $parameters = [];
    if (!empty($config['name'])) {
      $parameters = ['name' => $config['name']];
    }

    $create_result = $manager->create($container_config, $parameters);
    $container_id = $create_result->getId();

    // TODO: Ensure there are no stopped containers with the same name (currently throws fatal)
    $response = $manager->start($container_id);
    // TODO: Catch and exception if doesn't return 204.

    $service_container = $manager->find($container_id);
    $container['id'] = $service_container->getID();
    $container['name'] = $service_container->getName();
    $container['ip'] = $service_container->getNetworkSettings()->getIPAddress();
    $container['created'] = TRUE;
    $short_id = substr($container['id'], 0, 8);
    // OPUT
    Output::writeln("<comment>Container <options=bold>${container['name']}</options=bold> created from image <options=bold>${container['image']}</options=bold> with ID <options=bold>$short_id</options=bold></comment>");
  }

  protected function setDefaultCommand(&$config) {
    $config['Cmd'] = ['/bin/bash', '-c', '/daemon.sh'];
  }

  protected function createContainerLinks(&$config) {
    // DOCKER
    $links = [];
    if (empty($this->serviceContainers)) {
      return;
    }
    $targets = $this->serviceContainers;
    foreach ($targets as $type => $containers) {
      foreach ($containers as $key => $container) {
        $config['HostConfig']['Links'][] = "${container['name']}:${container['name']}";
      }
    }
  }

  protected function createContainerVolumes(&$config) {
    // DOCKER
    $volumes = [];
    // Map working directory
    // CODEBASE
    $working = $this->getCodebase()->getWorkingDir();
    // ENVIRONMENT - Volume mount for docker
    $mount_point = (empty($config['Mountpoint'])) ? "/data" : $config['Mountpoint'];
    $config['HostConfig']['Binds'][] = "$working:$mount_point";
  }

  public function getContainerConfiguration($image = NULL) {
    // DOCKER
    // ENVIRONMENT - container config directory
    $path = __DIR__ . '/../Containers';
    // RecursiveDirectoryIterator recurses into directories and returns an
    // iterator for each directory. RecursiveIteratorIterator then iterates over
    // each of the directory iterators, which consecutively return the files in
    // each directory.
    $directory = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
    $configs = [];
    foreach ($directory as $file) {
      if (!$file->isDir() && $file->isReadable() && $file->getExtension() === 'yml') {
        $container_name = $file->getBasename('.yml');
        $dev_suffix = '-dev';
        $isdev = strpos($container_name, $dev_suffix);
        if (!$isdev == 0) {
          $container_name = str_replace('-dev', ':dev', $container_name);
        }
        $image_name = 'drupalci/' . $container_name;
        if (!empty($image) && $image_name != $image) {
          continue;
        }
        // Get the default configuration.
        $container_config = Yaml::parse(file_get_contents($file->getPathname()));
        $configs[$image_name] = $container_config;
      }
    }
    return $configs;
  }

  public function startServiceContainerDaemons($container_type) {
    $needs_sleep = FALSE;
    // DOCKER
    $docker = $this->getDocker();
    $manager = $docker->getContainerManager();
    $instances = [];

    $images = $manager->findAll();

    foreach ($manager->findAll() as $running) {
      $running_container_name = explode(':', $running->getImage());
      $id = substr($running->getID(), 0, 8);
      $instances[$running_container_name[0]] = $id;
    };
    foreach ($this->serviceContainers[$container_type] as $key => $image) {
      if (in_array($image['image'], array_keys($instances))) {
        // TODO: Determine service container ports, id, etc, and save it to the build.
        // OPUT
        Output::writeln("<comment>Found existing <options=bold>${image['image']}</options=bold> service container instance.</comment>");
        // TODO: Load up container parameters
        $container = $manager->find($instances[$image['image']]);
        $container_id = $container->getID();
        $container_name = $container->getName();
        $container_ip = $container->getNetworkSettings()->getIPAddress();
        $this->serviceContainers[$container_type][$key]['id'] = $container_id;
        $this->serviceContainers[$container_type][$key]['name'] = $container_name;
        $this->serviceContainers[$container_type][$key]['ip'] = $container_ip;
        continue;
      }
      // Container not running, so we'll need to create it.
      // OPUT
      Output::writeln("<comment>No active <options=bold>${image['image']}</options=bold> service container instances found. Generating new service container.</comment>");

      // Get container configuration, which defines parameters such as exposed ports, etc.
      $configs = $this->getContainerConfiguration($image['image']);
      $config = $configs[$image['image']];
      // TODO: Allow classes to modify the default configuration before processing
      // Instantiate container

      // TODO: Use a normalizer
      $container_config = new ContainerConfig();
      $container_config->setImage($config['Image']);
      $host_config = new HostConfig();
      $host_config->setBinds($config['HostConfig']['Binds']);
      $container_config->setHostConfig($host_config);
      $parameters = [];
      if (!empty($config['name'])) {
        $parameters = ['name' => $config['name']];
      }

      $create_result = $manager->create($container_config, $parameters);
      $container_id = $create_result->getId();

      // Create the docker container instance, running as a daemon.
      // TODO: Ensure there are no stopped containers with the same name (currently throws fatal)
      $response = $manager->start($container_id);
      // TODO: Catch and exception if doesn't return 204.

      $container = $manager->find($container_id);

      $container_id = $container->getID();
      $container_name = $container->getName();
      $container_ip = $container->getNetworkSettings()->getIPAddress();

      $this->serviceContainers[$container_type][$key]['id'] = $container_id;
      $this->serviceContainers[$container_type][$key]['name'] = $container_name;
      $this->serviceContainers[$container_type][$key]['ip'] = $container_ip;
      $short_id = substr($container_id, 0, 8);
      // OPUT
      Output::writeln("<comment>Created new <options=bold>${image['image']}</options> container instance with ID <options=bold>$short_id</options=bold></comment>");
    }
    /* @var $database \DrupalCI\Build\Environment\Database */
    $database = $this->container['db.system'];
    // @TODO: should probably add the container environment as a service
    $database->setHost($container_ip);
    // @TODO: all of this should probably live inside of the database
    $database->connect();

  }

  public function getErrorState() {
    $results = $this->getBuildResults();
    return ($results->getResultByStep($results->getCurrentStage(), $results->getCurrentStep()) === "Error");
  }

  /**
   * Returns the default build definition template for this build type
   *
   * This method may be overridden by a specific build class to add template
   * selection logic, if desired.
   *
   * @param $build_type
   *   The name of the build type, used to select the appropriate subdirectory
   *
   * @return string
   *   The location of the default build definition template
   */
  public function getDefaultDefinitionTemplate($build_type) {
    // ENVIRONMENT - Build definition template"
    return __DIR__ . "/../../../build_templates/$build_type/drupalci.yml";
  }

  /**
   * Generate a Build ID for this build
   */
  public function generateBuildId() {
    // Use the BUILD_TAG environment variable if present, otherwise generate a
    // unique build tag based on timestamp.
    $build_id = getenv('BUILD_TAG');
    if (empty($build_id)) {
      $build_id = $this->getBuildType() . '_' . time();
    }
    $this->setBuildId($build_id);
    // OPUT
    Output::writeLn("<info>Executing build with build ID: <options=bold>$build_id</options=bold></info>");
  }

}
