<?php

/**
 * @file
 * Command class for Run.
 */

namespace DrupalCI\Console\Command\Run;

use DrupalCI\Console\Command\Drupal\DrupalCICommandBase;
use DrupalCI\Helpers\ConfigHelper;
use DrupalCI\Injectable;
use DrupalCI\Console\Output;
use DrupalCI\Build\Codebase\CodeBase;
use DrupalCI\Build\Definition\BuildDefinition;
use DrupalCI\Build\Results\BuildResults;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\PluginManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RunCommand extends DrupalCICommandBase  {

  /**
   * The Build this command is executing.
   *
   * @todo This needs to be replaced with a service
   * in the container.
   *
   * @var \DrupalCI\Build\BuildInterface
   */
  protected $build;

  /**
   * The build type plugin manager.
   *
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildTypePluginManager;

  /**
   * The build step plugin manager.
   *
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildStepPluginManager;

  /**
   * Gets the build from the RunCommand.
   *
   * @return \DrupalCI\Build\BuildInterface
   *   The build being ran.
   */
  public function getBuild() {
    return $this->build;
  }

  /**
   * Sets the build on the RunCommand.
   *
   * @param \DrupalCI\Build\BuildInterface $build
   *   The build and all its definition.
   */
  public function setBuild(BuildInterface $build) {
    $this->build = $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('run')
      ->setDescription('Execute a given build run.')
      // Argument may be the build type or a specific build definition file
      ->addArgument('definition', InputArgument::OPTIONAL, 'Build definition.');
  }

  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    $this->buildStepPluginManager = $this->container['plugin.manager.factory']->create('BuildSteps');
    $this->buildTypePluginManager = $this->container['plugin.manager.factory']->create('BuildTypes');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $arg = $input->getArgument('definition');

    $config_helper = new ConfigHelper();
    $this->buildVars->setAll($config_helper->getCurrentConfigSetParsed(), 'local');
    // @TODO: get rid of DCI_JobType plugins entirely.
    if (!empty($_ENV['DCI_JobType'])) {
      $this->buildVars->set('DCI_JobType',$_ENV['DCI_JobType'], 'local');
    }
    // Determine the Build Type based on the first argument to the run command
    if ($arg) {
      $build_type = (strtolower(substr(trim($arg), -4)) == ".yml") ? "generic" : trim($arg);
    } else {
      // If no argument defined, then check for a default in the local overrides
      $build_type = $this->buildVars->get('DCI_JobType', 'generic');
    }

    // WORKFLOW - buildtype shouldnt really be a plugin. This is where we should
    // Either read in the default build.yml or load a named build.yml, or
    // read in the build.yml from the command line.
    $this->build = $this->buildTypePluginManager->getPlugin($build_type, $build_type);


    $this->build->inject($this->container);

    // Link our $output variable to the build.
    // OPUT
    Output::setOutput($output);

    // Generate a unique build_id, and store it within the build object
    // WORKFLOW - Move this to the build object constructor
    $this->build->generateBuildId();

    // Create our build Definition object and attach it to the build.
    /* @var $build_definition \DrupalCI\Build\Definition\BuildDefinition */
    $build_definition = $this->container['build.definition'];
    $build_definition->inject($this->container);
    // WORKFLOW the build should get this from the container, not have a getter/setter for it.
    $this->build->setBuildDefinition($build_definition);

    // Compile our complete list of DCI_* variables
    // WORKFLOW - no, we should not compile a build.

    $build_definition->compile($this->build);

    // Create our build Codebase object and attach it to the build.
    // CODEBASE
    $codeBase = new CodeBase();
    $codeBase->inject($this->container);
    $this->build->setCodebase($codeBase);

    // Setup our project and version metadata
    // CODEBASE
    $codeBase->setupProject($build_definition);

    // Determine the build definition template to be used
    if ($arg && strtolower(substr(trim($arg), -4)) == ".yml") {
      $template_file = $arg;
    }
    else {
      $template_file = $this->build->getDefaultDefinitionTemplate($build_type);
    }
    // OPUT
    Output::writeLn("<info>Using build definition template: <options=bold>$template_file</options=bold></info>");

    // Load our build template file into the build definition.  If $template_file
    // doesn't exist, this will trigger a FileNotFound or ParseError exception.
    $build_definition->loadTemplateFile($template_file);

    // Process the complete build definition, taking into account DCI_* variable
    // and definition preprocessors, along with build-specific arguments
    $build_definition->preprocess($this->build);

    // Validate the resulting build definition, to ensure all required parameters
    // are present.
    $result = $build_definition->validate($this->build);
    if (!$result) {
      // Build definition failed validation.  Error output has already been
      // generated and displayed during execution of the validation method.
      return;
    }

    // Set up the local working directory
    // CODEBASE
    $result = $codeBase->setupWorkingDirectory($build_definition);
    if ($result === FALSE) {
      // Error encountered while setting up the working directory. Error output
      // has already been generated and displayed during execution of the
      // setupWorkingDirectory method.
      return;
    }

    // Create our build Results object and attach it to the build.
    $build_results = new BuildResults($this->build);
    $this->build->setBuildResults($build_results);

    // The build should now have a fully merged build definition file, including
    // any local or DrupalCI defaults not otherwise defined in the passed build
    // definition
    $definition = $build_definition->getDefinition();

    // Iterate over the build stages
    foreach ($definition as $build_stage => $steps) {
      if (empty($steps)) {
        $build_results->updateStageStatus($build_stage, 'Skipped');
        continue;
      }
      $build_results->updateStageStatus($build_stage, 'Executing');

      // Iterate over the build steps
      foreach ($steps as $build_step => $data) {
        $build_results->updateStepStatus($build_stage, $build_step, 'Executing');
        // Execute the build step
        $this->buildStepPluginManager->getPlugin($build_stage, $build_step)->run($this->build, $data);

        // Check for errors / failures after build step execution
        $status = $build_results->getResultByStep($build_stage, $build_step);
        if ($status == 'Error') {
          // Step returned an error.  Halt execution.
          // OPUT
          Output::error("Execution Error", "Error encountered while executing build step <options=bold>$build_stage:$build_step</options=bold>");
          break 2;
        }
        if ($status == 'Fail') {
          // Step returned an failure.  Halt execution.
          // OPUT
          Output::error("Execution Failure", "Build step <options=bold>$build_stage:$build_step</options=bold> FAILED");
          break 2;
        }
        $build_results->updateStepStatus($build_stage, $build_step, 'Completed');
      }
      $build_results->updateStageStatus($build_stage, 'Completed');
    }
    // TODO: Gather results.
    // This should be moved out of the 'build steps' logic, as an error in any
    // build step halts execution of the entire loop, and the artifacts are not
    // processed.

  }
}
