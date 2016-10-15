<?php
/**
 * @file
 * Base Build class for DrupalCI.
 */

namespace DrupalCI\Build;

use Docker\API\Model\ContainerConfig;
use Docker\API\Model\HostConfig;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Console\Output;
use DrupalCI\Injectable;
use DrupalCI\InjectableTrait;
use DrupalCI\Build\Results\Artifacts\BuildArtifact;
use DrupalCI\Build\Results\Artifacts\BuildArtifactList;
use DrupalCI\Build\Codebase\CodeBase;
use DrupalCI\Build\Definition\BuildDefinition;
use DrupalCI\Build\Results\BuildResults;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tests\Output\ConsoleOutputTest;
use Symfony\Component\Process\Process;
use Docker\Docker;
use Docker\DockerClient as Client;
use Symfony\Component\Yaml\Yaml;
use PDO;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\ConsoleEvents;

class BuildBase implements BuildInterface, Injectable {

  use InjectableTrait;

  /**
   * Stores the build type
   *
   * @var string
   */
  protected $buildType = 'base';
  public function getBuildType() {  return $this->buildType;  }

  /**
   * Stores the calling command's output buffer
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  public $output;
  public function setOutput(OutputInterface $output) {  $this->output = $output;  }
  public function getOutput() {  return $this->output;  }

  /**
   * Stores a build ID for this build
   *
   * @var string
   */
  protected $buildId;
  public function getBuildId() {  return $this->buildId;  }
  public function setBuildId($buildId) {  $this->buildId = $buildId;  }

  /**
   * Stores the pift-ci-job id for this build.
   *
   * @var string
   */
  protected $drupalOrgBuildId;
  public function getDrupalOrgBuildId() {  return $this->drupalOrgBuildId;  }
  public function setDrupalOrgBuildId($drupalOrgBuildId) {  $this->drupalOrgBuildId = $drupalOrgBuildId;  }

  /**
   * Stores the jenkins build id for this build.
   *
   * @var string
   */
  protected $jenkinsBuildId;
  public function getJenkinsBuildId() {  return $this->jenkinsBuildId;  }
  public function setJenksinBuildId($jenkinsBuildId) {  $this->jenkinsBuildId = $jenkinsBuildId;  }


  /**
   * Stores the build definition object for this build
   *
   * @var \DrupalCI\Build\Definition\BuildDefinition
   */
  protected $buildDefinition = NULL;
  public function getBuildDefinition() {  return $this->buildDefinition;  }
  public function setBuildDefinition(BuildDefinition $build_definition) {
    $build_definition->setContainer($this->container);
    $this->buildDefinition = $build_definition;
  }

  /**
   * Stores the codebase object for this build
   *
   * @var \DrupalCI\Build\Codebase\CodeBase
   */
  protected $codeBase;
  public function getCodebase() {  return $this->codeBase;  }
  public function setCodebase(CodeBase $codeBase)  {  $this->codeBase = $codeBase;  }

  /**
   * Stores the results object for this build
   *
   * @var \DrupalCI\Build\Results\BuildResults
   */
  protected $buildResults;
  public function getBuildResults() {  return $this->buildResults;  }
  public function setBuildResults(BuildResults $build_results)  {  $this->buildResults = $build_results;  }

  /**
   * Defines argument variable names which are valid for this build type
   *
   * @var array
   */
  protected $availableArguments = array();
  public function getAvailableArguments() {  return $this->availableArguments;  }

  /**
   * Defines the default arguments which are valid for this build type
   *
   * @var array
   */
  protected $defaultArguments = array();
  public function getDefaultArguments() {  return $this->defaultArguments;  }

  /**
   * Defines the required arguments which are necessary for this build type
   *
   * Format:  array('ENV_VARIABLE_NAME' => 'CONFIG_FILE_LOCATION'), where
   * CONFIG_FILE_LOCATION is a colon-separated nested location for the
   * equivalent variable in a build definition file.
   *
   * @var array
   */
  protected $requiredArguments = array();   // eg:   'DCI_DBVersion' => 'environment:db'
  public function getRequiredArguments() {  return $this->requiredArguments;  }

  /**
   * Defines initial platform defaults for all builds (if not overridden).
   *
   * @var array
   */
  protected $platformDefaults = array(
    "DCI_CoreProject" => "Drupal",
  );
  public function getPlatformDefaults() {  return $this->platformDefaults;  }

  /**
   * Stores build variables which need to be persisted between build steps
   *
   * @var array
   */
  protected $buildVars = array();
  public function getBuildVars() {  return $this->buildVars;  }
  public function setBuildVars(array $build_vars) {  $this->buildVars = $build_vars;  }
  public function getBuildVar($build_var) {  return isset($this->buildVars[$build_var]) ? $this->buildVars[$build_var] : NULL;  }
  public function setBuildVar($build_var, $value) {  $this->buildVars[$build_var] = $value;  }

  /**
   * Stores our Docker Container manager
   *
   * @var \Docker\Docker
   */
  protected $docker;

  /**
   * @return \Docker\Docker
   */
  public function getDocker()
  {
    $client = Client::createFromEnv();
    if (null === $this->docker) {
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
    return $this->serviceContainers;
  }

  public function setServiceContainers(array $service_containers) {
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
    $this->executableContainers = $containers;
  }

  public function startContainer(&$container) {
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
      $parameters = [ 'name' => $config['name'] ];
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
    Output::writeln("<comment>Container <options=bold>${container['name']}</options=bold> created from image <options=bold>${container['image']}</options=bold> with ID <options=bold>$short_id</options=bold></comment>");
  }

  protected function setDefaultCommand(&$config) {
    $config['Cmd'] = ['/bin/bash', '-c', '/daemon.sh'];
  }

  protected function createContainerLinks(&$config) {
    $links = array();
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
    $volumes = array();
    // Map working directory
    $working = $this->getCodebase()->getWorkingDir();
    $mount_point = (empty($config['Mountpoint'])) ? "/data" : $config['Mountpoint'];
    $config['HostConfig']['Binds'][] = "$working:$mount_point";
  }

  public function getContainerConfiguration($image = NULL) {
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
        if ( !$isdev == 0) {
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
    $docker = $this->getDocker();
    $manager = $docker->getContainerManager();
    $instances = array();

    $images = $manager->findAll();

    foreach ($manager->findAll() as $running) {
      $running_container_name = explode(':',$running->getImage());
      $id = substr($running->getID(), 0, 8);
      $instances[$running_container_name[0]] = $id;
    };
    foreach ($this->serviceContainers[$container_type] as $key => $image) {
      if (in_array($image['image'], array_keys($instances))) {
        // TODO: Determine service container ports, id, etc, and save it to the build.
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
        $parameters = [ 'name' => $config['name'] ];
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
      Output::writeln("<comment>Created new <options=bold>${image['image']}</options> container instance with ID <options=bold>$short_id</options=bold></comment>");
    }

    $dburl_parts = parse_url($this->buildVars['DCI_DBUrl']);
    $dburl_parts['host'] = $container_ip;
    if(!strpos('sqlite', $dburl_parts['scheme'])){
      $counter = 0;
      $increment = 10;
      $max_sleep = 120;
      while($counter < $max_sleep ){
        if ($this->checkDBStatus($dburl_parts)){
          Output::writeln("<comment>Database is active.</comment>");
          break;
        }
        if ($counter >= $max_sleep){
          Output::writeln("<error>Max retries reached, exiting promgram.</error>");
          exit(1);
        }
        Output::writeln("<comment>Sleeping " . $increment . " seconds to allow service to start.</comment>");
        sleep($increment);
        $counter += $increment;

      }
    }
  }

  public function checkDBStatus($dburl_parts)
  {
    if(strcmp('mariadb',$dburl_parts['scheme']) === 1){
      $dburl_parts['scheme'] = 'mysql';
    }
    try {
      $conn_string = $dburl_parts['scheme'] . ':host=' . $dburl_parts['host'];
      Output::writeln("<comment>Attempting to connect to database server.</comment>");
      $conn = new PDO($conn_string, $dburl_parts['user'], $dburl_parts['pass']);
    } catch (\PDOException $e) {
      Output::writeln("<comment>Could not connect to database server.</comment>");
      return FALSE;
    }
    return TRUE;
  }

  public function getErrorState() {
    $results = $this->getBuildResults();
    return ($results->getResultByStep($results->getCurrentStage(), $results->getCurrentStep()) === "Error");
  }

  /**
   * @var /DrupalCI/Build/Results/Artifacts/BuildArtifactList
   */
  protected $artifacts;
  public function setArtifacts($artifacts) { $this->artifacts = $artifacts; }
  public function getArtifacts() { return $this->artifacts; }

  public function __construct() {
    $this->createArtifactList();
  }

  protected function createArtifactList() {
    if (!isset($this->artifacts)) {
      $this->artifacts = New BuildArtifactList();
    }
    // Load the standard base build artifacts into the list
    foreach($this->defaultBuildArtifacts as $key => $value) {
      $artifact = New BuildArtifact('file', $value);
      $this->artifacts->addArtifact($key, $artifact);
    }
    // Load the buildType specific build artifacts into the list
    // Format: array(key, target, [type = file])
    foreach ($this->buildArtifacts as $value) {
      $key = $value[0];
      $target = $value[1];
      $type = isset($value[2]) ? $value[2] : 'file';
      $artifact = New BuildArtifact($type, $target);
      $this->artifacts->addArtifact($key, $artifact);
    }
  }

  // Provide the default file locations for standard build artifacts.
  protected $defaultBuildArtifacts = array(
    //'stdout' => 'stdout.txt',
    //'stderr' => 'stderr.txt',
    'buildDefinition' => 'buildDefinition.txt',
  );

  /**
   * Provide the details for build-specific build artifacts.
   *
   * This should be overridden by build-specific classes, to define the build
   * artifacts which should be collected for that class.
   *
   * The default build artifacts listed above can be overridden here as well.
   */
  protected $buildArtifacts = array(
    // e.g. phpunit results file at ./results.txt:
    // array('phpunit_results', './results.txt'),
    // e.g. multiple xml files within results/xml directory:
    // array('xml_results', 'results/xml', 'directory')
    // e.g. a string representing red/blue outcome:
    // array('color', 'red', 'string')
  );

  protected $artifactDirectory;

  /**
   * @param mixed $artifactDirectory
   */
  public function setArtifactDirectory($artifactDirectory)
  {
    $this->artifactDirectory = $artifactDirectory;
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
    Output::writeLn("<info>Executing build with build ID: <options=bold>$build_id</options=bold></info>");
  }

}
