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
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tests\Output\ConsoleOutputTest;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Docker\Docker;
use Docker\DockerClient as Client;
use Symfony\Component\Yaml\Exception\ParseException;
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
   * @var array
   *
   *   Hierarchical array of configured plugins
   */
  protected $computedBuildPlugins;

  /**
   * The build task plugin manager.
   *
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildTaskPluginManager;

  /**
   * @var \Symfony\Component\Yaml\Yaml
   *
   *   Parsed Yaml of the build definition.
   */
  protected $yaml;

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  protected $buildDirectory;

  // @TODO: build should do something with configuration values in the build.yml
  protected $configuration;



  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    $this->container = $container;
    $this->io = $container['console.io'];
    $this->yaml = $container['yaml.parser'];
    $this->buildTaskPluginManager = $this->container['plugin.manager.factory']->create('BuildTask');
  }

  /**
   * Stores the build type
   *
   * @var string
   */
  protected $buildType;

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
   * @param $arg
   *
   * Takes in either the full path to a build.yml file, or the name of one of
   * the predefined build_definitions like simpletest or simpletest7, or if
   * null, defaults to simpletest.  Once it loads the yaml definition, it
   * recursively iterates over the definition creating and configuring the
   * build plugins for this build.
   */
  public function generateBuild($arg) {


    if (false !== getenv('DCI_JobType')) {
      $arg = getenv('DCI_JobType');
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
    $this->computedBuildDefinition = $this->initialBuildDefinition['build'];
    $this->computedBuildPlugins = $this->processBuildConfig($this->computedBuildDefinition);
    $build_definition['build'] = $this->computedBuildDefinition;

    $this->generateBuildId();
    $this->setupWorkSpace();
    $this->saveYaml($build_definition);

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
  protected function processBuildConfig(&$config, &$transformed_config = [], $depth = 0) {
    // $depth determines which type of plugin we're after.
    // There is no BuildStepConfig, but if we're at depth 3, thats what we
    // fake ourselves into believing, because everything at that level is
    // configuration for the level above.
    $task_type = ['BuildStage','BuildPhase','BuildStep','BuildStepConfig'];
    foreach ($config as $config_key => $task_configurations) {
      $plugin_key = preg_replace('/\..*/', '', $config_key);
      if ($this->buildTaskPluginManager->hasPlugin($task_type[$depth], $plugin_key)) {
        // This $config_key is a BuildTask plugin, therefore it may have some
        // configuration definedor may have child BuildTask plugins.
        $transformed_config[$config_key] = [];
        // If a task_configuration is null, that indicates that this BuildTask
        // has no configuration overrides, or subordinate children.
        if (!is_null($task_configurations)) {
          $depth++;
          $processed_config = $this->processBuildConfig($task_configurations, $transformed_config[$config_key], $depth);
          // Also, perhaps we check if $depth = 3 and go ahead and redo the else
          // below?
          // Bubble the configuration change back up.
          $config[$config_key] = $task_configurations;
          $depth--;
          // If it has configuration, lets remove it from the array and use it
          // later to create our plugin.
          if (isset($processed_config['#configuration'])) {
            $overrides = $processed_config['#configuration'];
            unset($transformed_config[$config_key]['#configuration']);
          }
          else {
            $overrides = [];
          }
          $children = $transformed_config[$config_key];
          unset($transformed_config[$config_key]);
          $transformed_config[$config_key]['#children'] = $children;
          /* @var $plugin \DrupalCI\Plugin\BuildTask\BuildTaskInterface */
          $plugin = $this->buildTaskPluginManager->getPlugin($task_type[$depth], $plugin_key, $overrides);
          // TODO: setChildTasks should probably be set on the BuildTaskTrait.
          // But lets wait until we're sure we need it for something.
          // $plugin->setChildTasks($children);
          $transformed_config[$config_key]['#plugin'] = $plugin;

        } else {
          $transformed_config[$config_key]['#plugin'] = $this->buildTaskPluginManager->getPlugin($task_type[$depth],$plugin_key);

        }
        if (!empty($config[$config_key])) {
          $config[$config_key] = array_merge($config[$config_key], $transformed_config[$config_key]['#plugin']->getComputedConfiguration());
        } else {
          $config[$config_key] = $transformed_config[$config_key]['#plugin']->getComputedConfiguration();
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
    try {
      $statuscode = $this->processTask($this->computedBuildPlugins);
      $this->saveBuildState();
      return $statuscode;
    }
    catch (BuildTaskException $e) {
      $this->saveBuildState($e->getMessage());
      return 2;
    } finally {
      try {
        // If we set DCI_Debug, we keep the databases n stuff.
        if (FALSE === (getenv('DCI_Debug'))){
            $this->cleanupBuild();
        }
      }
      catch (\Exception $e) {
        $this->io->drupalCIError('Failure in build cleanup', $e->getMessage());
      }
    }

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
    $total_status = 0;
    foreach ($taskConfig as $task) {
      // Each task is an array, so that we can support running the same task
      // multiple times.

      // TODO: okay, this is already a hot mess. Interacting with an
      // implied array strucuture is not what we want here: this needs to be
      // an Object.
      /* @var $plugin \DrupalCI\Plugin\BuildTask\BuildTaskInterface */
      $plugin = $task['#plugin'];
      $child_status = 0;
      // start also implies run();
      $task_status = $plugin->start();
      if (isset($task['#children'])) {
        $child_status = $this->processTask($task['#children']);
      }
      // Allow plugins to react based on the status of executed children
      $plugin->finish($child_status);
      $total_status = max($task_status, $child_status, $total_status);

    }
    return $total_status;
  }

  /**
   * Checks if an array has string keys.
   * @param array $array
   *
   * @return bool
   */
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
      return $this->yaml->parse($content);
    }
    throw new ParseException("Unable to parse build definition file at $source.");
  }

  /**
   * Given a file, returns an array containing the parsed YAML contents from that file
   *
   * @param $config
   *
   * @TODO refactor out the buildfile and pass it as an arg too.
   */
  protected function saveYaml($config) {

    $buildfile = $this->getArtifactDirectory() . '/build.' . $this->getBuildId() . '.yml';
    $yamlstring = $this->yaml->dump($config);
    file_put_contents($buildfile, $yamlstring);

  }

  /**
   * Given a file, returns an array containing the parsed YAML contents from that file
   *
   * @param $message
   *
   *
   * @TODO refactor out the buildfile and pass it as an arg too.
   */
  protected function saveBuildState($message = 'Build Successful') {

    $buildstate = $this->getArtifactDirectory() . '/buildstate.json';
    $json = json_encode($message);
    file_put_contents($buildstate, $json);

  }

  /**
   * {@inheritdoc}
   */
  public function getBuildDirectory() {
    return $this->buildDirectory;
  }

  /**
   * @inheritDoc
   */
  public function getArtifactDirectory() {
    return $this->buildDirectory . '/artifacts';
  }
  /**
   * @inheritDoc
   */
  public function getXMLDirectory() {
    return $this->getArtifactDirectory() . '/xml';
  }

  /**
   * @inheritDoc
   */
  public function getSourceDirectory() {
    return $this->buildDirectory . '/source';
  }

  /**
   * @inheritDoc
   */
  public function getDBDirectory() {
    return $this->buildDirectory . '/database';
  }



  /**
   * Generate a Build ID for this build
   */
  public function generateBuildId() {
    // Use the BUILD_TAG environment variable if present, otherwise generate a
    // unique build tag based on timestamp.
    $build_id = getenv('BUILD_TAG');
    if (empty($build_id)) {
      $build_id = $this->buildType . '_' . time();
    }
    $this->setBuildId($build_id);
    $this->io->writeLn("<info>Executing build with build ID: <options=bold>$build_id</options=bold></info>");
  }

  /**
   * @return bool
   */
  protected function setupWorkSpace() {
    // Check if the target working directory has been specified in the env.
    if (false !== (getenv('DCI_WorkingDir'))) {
      $build_directory = getenv('DCI_WorkingDir');
    }
    // Both the AMI and Vagrant box defines this as /var/lib/drupalci/web
    $tmp_directory = sys_get_temp_dir();
    // Generate a default directory name if none specified
    if (empty($build_directory)) {
      // Case:  No explicit working directory defined.
      $build_directory = $tmp_directory . '/' . $this->buildId;
    }
    else {
      // We force the working directory to always be under the system temp dir.
      if (strpos($build_directory, realpath($tmp_directory)) !== 0) {
        if (substr($build_directory, 0, 1) == '/') {
          $build_directory = $tmp_directory . $build_directory;
        }
        else {
          $build_directory = $tmp_directory . '/' . $build_directory;
        }
      }
    }
    $result = $this->setupDirectory($build_directory);
    if (!$result) {
      return FALSE;
    }


    // Validate that the working directory is empty.  If the directory contains
    // an existing git repository, for example, our checkout attempts will fail
    // TODO: Prompt the user to ask if they'd like to overwrite
    $iterator = new \FilesystemIterator($build_directory);
    if ($iterator->valid()) {
      // Existing files found in directory.
      $this->io->drupalCIError('Directory not empty', 'Unable to use a non-empty working directory.');
      return FALSE;
    };

    // Convert to the full path and ensure our directory is still valid
    $build_directory = realpath($build_directory);
    if (!$build_directory) {
      // Directory not found after conversion to canonicalized absolute path
      $this->io->drupalCIError('Directory not found', 'Unable to determine working directory absolute path.');
      return FALSE;
    }

    // Ensure we're still within the system temp directory
    if (strpos(realpath($build_directory), realpath($tmp_directory)) !== 0) {
      $this->io->drupalCIError('Directory error', 'Detected attempt to traverse out of the system temp directory.');
      return FALSE;
    }

    // If we arrive here, we have a valid empty working directory.
    $this->buildDirectory = $build_directory;



    $result =  $this->setupDirectory($this->getArtifactDirectory());
    if (!$result) {
      return FALSE;
    }
    $result =  $this->setupDirectory($this->getSourceDirectory());
    if (!$result) {
      return FALSE;
    }
    $result =  $this->setupDirectory($this->getDBDirectory());
    if (!$result) {
      return FALSE;
    }
    $result =  $this->setupDirectory($this->getXMLDirectory());
    if (!$result) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $directory
   *
   * @return bool
   */
  protected function setupDirectory($directory): bool {
    if (!is_dir($directory)) {
      $result = mkdir($directory, 0777, TRUE);
      if (!$result) {
        // Error creating checkout directory
        $this->io->drupalCIError('Directory Creation Error', 'Error encountered while attempting to create  directory');
        return FALSE;
      }
      else {
        $this->io->writeLn("<info>Directory created at <options=bold>$directory</options=bold></info>");
        return TRUE;
      }
    }
    return TRUE;
  }


  /**
   * This function removes any databases, cleans up any source files, and stops
   * any running containers.
   *
   * @TODO: this needs some reworking, because ideally none of this code
   * should live here in the build, and the build objects themselves
   * ought to know how to clean up after themselves.
   *
   * Probably what needs to happen in the build needs to be an iterable tree,
   * and that tree gets iterated over several times, once to run the start and
   * finish callbacks, and perhaps once to run the cleanup callbacks.
   *
   */
  protected function cleanupBuild() {

    /* @var $environment \DrupalCI\Build\Environment\Environment */

    // Open up permissions on containers.
    $uid = posix_getuid();
    $environment = $this->container['environment'];
    $commands = [
                 'chown -R '. $uid . ' /var/www/html',
                 'chmod -R 777 /var/www/html',
                ];
    $environment->executeCommands($commands);
    $db_container = $environment->getDatabaseContainer();
    $db_dir = $this->container['db.system']->getDataDir();
    $commands = [
      'chown -R '. $uid . ' ' . $db_dir,
      'chmod -R 777 ' . $db_dir,
    ];
    $environment->executeCommands($commands, $db_container['id']);

    // Shut off the containers
    $this->container['environment']->terminateContainers();

    // Delete the source code and database files
    $fs = new Filesystem();
    $fs->remove($this->getSourceDirectory());
    $fs->remove($this->getDBDirectory());
  }

}
