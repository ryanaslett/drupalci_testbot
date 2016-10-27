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
use DrupalCI\Build\Codebase\Codebase;
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
   * @var string
   *
   * This is the file that contains the yaml that defines this build.
   */
  protected $buildFile;

  /**
   * @var array
   *
   *   Parsed Yaml of the build definition.
   */
  protected $buildDefinition;

  /**
   * @var array
   *
   *   Parsed Yaml of the build definition.
   */
  protected $initialBuildDefinition;

  /**
   * @var array
   *
   *   Hierarchical array representing order of plugin execution and
   *   overridden configuration options.
   */
  protected $computedBuildDefinition;

  /**
   * The build task plugin manager.
   *
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildTaskPluginManager;

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
   * {@inheritdoc}
   */
  public function getBuildFile() {
    return $this->buildFile;
  }

  /**
   * {@inheritdoc}
   */
  public function setBuildFile($buildFile) {
    $this->buildFile = $buildFile;
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
   * Stores the build definition array for this build
   * @return array
   */
  public function getBuildDefinition() {
    return $this->buildDefinition;
  }


  /**
   * @param $arg
   *
   * Takes in either the full path to a build.yml file, or the name of one of
   * the predefined build_definitions like simpletest or simpletest7, or if
   * null, defaults to simpletest.  Once it loads the yaml definition, it
   * recursively iterates over the definition creating and configuring the
   * build plugins for this build.
   */
  public function generateBuild($arg) {


    if (isset($_ENV['DCI_JobType'])) {
      $arg = $_ENV['DCI_JobType'];
    }
    if ($arg) {
      if (strtolower(substr(trim($arg), -4)) == ".yml") {
        $this->buildFile = $arg;
        $this->buildType = 'custom';
      }
      else {
        $this->buildFile = $this->container['app.root'] . '/build_definitions/' . $arg . '.yml';
        $this->buildType = $arg;
      }
    }
    else {
      // If no argument defined, then we assume the default of simpletest

      $this->buildFile = $this->container['app.root'] . '/build_definitions/simpletest.yml';
      $this->buildType = 'simpletest';
    }

    $this->initialBuildDefinition = $this->loadYaml($this->buildFile);
    // After we load the config, we separate the workflow from the config:
    $this->computedBuildDefinition = $this->processBuildConfig($this->initialBuildDefinition['build']);
    $this->generateBuildId();

  }


  /**
   * Recursive function that iterates over a build configuration and extracts
   * The build workflow, and overridden configuration for each build task.
   * If a key happens to be a build plugin key we go deeper to split out its
   * configuration from its child BuildTasks
   *
   * // Rules for reading in the build.yml file
   * Check to see if the key is a plugin:
   * If the key is an array, OR the key is null, then we check to see if the
   * key is a plugin.
   * If the key is *not* a plugin, then it is assumed to be configuration data
   * For the current level. (Build, BuildStage, BuildPhase, BuildTask)
   *
   * @TODO: this awful mess should be constructing a proper object that can
   * be iterated over, using spl_object_hash to make keys for the objects
   * RecursiveIteratorIterator would be handy too. But this proves it can work.
   *
   */
  protected function processBuildConfig($config, &$transformed_config = [], $depth = 0) {
    // $depth determines which type of plugin we're after.
    // There is no BuildStepConfig, but if we're at depth 3, thats what we
    // fake ourselves into believing, because everything at that level is
    // configuration for the level above.
    $task_type = ['BuildStage','BuildPhase','BuildStep','BuildStepConfig'];
    foreach ($config as $config_key => $task_configurations) {

      if ($this->buildTaskPluginManager->hasPlugin($task_type[$depth], $config_key)) {
        // This $config_key is a BuildTask plugin, therefore it may have some
        // configuration definedor may have child BuildTask plugins.
        $transformed_config[$config_key] = [];
        // If a task_configuration is null, that indicates that this BuildTask
        // has no configuration overrides, or subordinate children.
        if (!is_null($task_configurations)) {
          if ($this->has_string_keys($task_configurations)) {
            // Convert non-array configs into an array of config
            $task_configurations = [0 => $task_configurations];
          }
          foreach ($task_configurations as $index => $configuration) {
            $depth++;
            $processed_config = $this->processBuildConfig($configuration, $transformed_config[$config_key][$index], $depth);
            // Also, perhaps we check if $depth = 3 and go ahead and redo the else
            // below?
            $depth--;
            // If it has configuration, lets remove it from the array and use it
            // later to create our plugin.
            if (isset($processed_config['#configuration'])) {
              $overrides = $processed_config['#configuration'];
              unset($transformed_config[$config_key][$index]['#configuration']);
            }
            else {
              $overrides = [];
            }
            $children = $transformed_config[$config_key][$index];
            unset($transformed_config[$config_key][$index]);
            $transformed_config[$config_key][$index]['#children'] = $children;
            /* @var $plugin \DrupalCI\Plugin\BuildTask\BuildTaskInterface */
            $plugin = $this->buildTaskPluginManager->getPlugin($task_type[$depth], $config_key, $overrides);
            // TODO: setChildTasks should probably be set on the BuildTaskTrait.
            // But lets wait until we're sure we need it for something.
            // $plugin->setChildTasks($children);
            $transformed_config[$config_key][$index]['#plugin'] = $plugin;
          }
        } else {
          $transformed_config[$config_key][0]['#plugin'] = $this->buildTaskPluginManager->getPlugin($task_type[$depth],$config_key);
        }
      } else {
        // The key is not a plugin, therefore it is a configuration directive for the plugin above it.
        $transformed_config['#configuration'][$config_key] = $config[$config_key];
      }
    }
    return $transformed_config;
  }

  /**
   * Iterates over the configured hierarchy of configured BuildTasks and
   * processes the build.
   */
  public function executeBuild() {
    $something = $this->processTask($this->computedBuildDefinition);

  }

  protected function processTask($taskConfig) {
    /*
         * Foreach BuildTask, Do
         * $build->processTask (recursive build processor)
         *
         * processTask:
         * start() the buildtask, which starts the timer and then run() it
         * Most of the work of a buildtask is going to happen here. For BuildStages
         * and BuildPhases, there probably wont be too much to do besides set up
         * some Build objects.
         * $buildtask->start() [this implies run() ]
         * Once we've run this tasks start()/run(), Then we'll recurse into the children
         * foreach ($buildtask->getChildTasks()) {
         *     $continue = $this->processTask($remainder_of_definition);
         *     if ($continue = FALSE) {
         *       stop processing tasks and return FALSE.
         *     }
         * }
         * then we $buildtask->finish to post process child tasks as well as the
         * current task.
         *
         * start->run->complete->getArtifacts->finish.
         * A Task can fail the build. by returning False value from
         * processTask indicates proceed, or abort.
         *
         * When we get artifacts from the task, that takes whatever build artifacts
         * are defined by the task and relocates them to the build's main artifact
         * directory.  The build is responsible for re-naming the artifacts - that
         * way if there are two junit.xml outputs from subsequent runtests, the
         * build can place them in the right place.
         *
         *
         * $buildtask->
         */

    foreach ($taskConfig as $task) {
      // Each task is an array, so that we can support running the same task
      // multiple times.
      foreach ($task as $iteration) {
        // TODO: okay, this is already a hot mess. Interacting with an
        // implied array strucuture is not what we want here: this needs to be
        // an Object.
        /* @var $plugin \DrupalCI\Plugin\BuildTask\BuildTaskInterface */
        $plugin = $iteration['#plugin'];
        // start also implies run();
        $plugin->start($this);
        if (isset($iteration['#children'])) {
          $this->processTask($iteration['#children']);
        }
        $plugin->finish();
      }
    }
  }

  protected function has_string_keys(array $array) {
    return count(array_filter(array_keys($array), 'is_string')) > 0;
  }

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
   * @var \DrupalCI\Build\Codebase\Codebase
   */
  // CODEBASE
  protected $codebase;

  public function getCodebase() {
    return $this->codebase;
  }

  public function setCodebase(Codebase $codebase) {
    $this->codebase = $codebase;
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
      // TODO: potential collision if multiple invocations of drupalci are
      // running on the same machine in parallel and they start up at the same
      // time.
      $build_id = $this->buildType . '_' . time();
    }
    $this->setBuildId($build_id);
    // OPUT
    Output::writeLn("<info>Executing build with build ID: <options=bold>$build_id</options=bold></info>");
  }

}
