<?php

/**
 * @file
 * Command class for Run.
 */

namespace DrupalCI\Console\Command\Run;

use DrupalCI\Console\Command\Drupal\DrupalCICommandBase;
use DrupalCI\Injectable;
use DrupalCI\Console\Output;
use DrupalCI\Build\Codebase\Codebase;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\PluginManager;
use Pimple\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RunCommand extends DrupalCICommandBase  {

  /**
   * The Build this command is executing.
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
   * The build task plugin manager.
   *
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildTaskPluginManager;

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
      // Argument may be the build type or the path to a specific build definition file
      ->addArgument('definition', InputArgument::OPTIONAL, 'Build definition.');
  }

  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    $this->buildStepPluginManager = $this->container['plugin.manager.factory']->create('BuildSteps');
    $this->buildTypePluginManager = $this->container['plugin.manager.factory']->create('BuildTypes');
    $this->buildTaskPluginManager = $this->container['plugin.manager.factory']->create('BuildTask');
    // Yeah, a build isnt really a service, but for now it is.
    /* @var \DrupalCI\Build\BuildInterface */
    $this->build = $this->container['build'];

  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {

    $arg = $input->getArgument('definition');
    $this->build->inject($this->container);
    $this->build->generateBuild($arg);

    // Create our build Codebase object and attach it to the build.
    // CODEBASE
    $codebase = new Codebase();
    $codebase->inject($this->container);
    $this->build->setCodebase($codebase);
    $this->io->writeln("<info>Using build definition template: <options=bold>" . $this->build->getBuildFile() ."</options></options=bold></info>");

    // Set up the local working directory
    // CODEBASE
    $result = $codebase->setupWorkingDirectory($this->build->getBuildId());
    if ($result === FALSE) {
      // Error encountered while setting up the working directory. Error output
      // has already been generated and displayed during execution of the
      // setupWorkingDirectory method.
      return;
    }

    // Execute the build.
    $this->build->executeBuild();

  }
}
