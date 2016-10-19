<?php
/**
 * @file
 * Contains
 */
namespace DrupalCI\Build;

use DrupalCI\Build\Codebase\CodeBase;
use DrupalCI\Build\Definition\BuildDefinition;
use DrupalCI\Build\Results\Artifacts\BuildArtifactList;
use DrupalCI\Build\Results\BuildResults;
use Symfony\Component\Console\Output\OutputInterface;

interface BuildInterface {

  /**
   * @return string
   */
  public function getBuildType();

  /**
   * @return \Symfony\Component\Console\Output\OutputInterface
   */
  public function getOutput();

  /**
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  public function setOutput(OutputInterface $output);

  /**
   * @return string
   */
  public function getBuildId();

  /**
   * @param string
   */
  public function setBuildId($id);

  /**
   * @return \DrupalCI\Build\Definition\BuildDefinition
   */
  public function getBuildDefinition();

  /**
   * @param \DrupalCI\Build\Definition\BuildDefinition $build_definition
   */
  public function setBuildDefinition(BuildDefinition $build_definition);

  /**
   * @return \DrupalCI\Build\Codebase\CodeBase
   */
  // CODEBASE
  public function getCodebase();

  /**
   * @param \DrupalCI\Build\Codebase\CodeBase $codeBase
   */
  // CODEBASE
  public function setCodebase(CodeBase $codeBase);

  /**
   * @return \DrupalCI\Build\Results\BuildResults
   */
  public function getBuildResults();

  /**
   * @param \DrupalCI\Build\Results\BuildResults $build_results
   */
  public function setBuildResults(BuildResults $build_results);


  /**
   * Available arguments.
   *
   * @TODO: move to annotation
   *
   * @return array
   *
   * @see SimpletestBuild::$availableArguments
   */
  public function getAvailableArguments();

  /**
   * Default arguments.
   *
   * @TODO: move to annotation
   *
   * @return array
   *
   * @see SimpletestBuild::$defaultArguments
   */
  public function getDefaultArguments();

  /**
   * Required arguments.
   *
   * @TODO: move to annotation
   *
   * @return array
   *
   * @see SimpletestBuild::$requiredArguments
   */
  public function getRequiredArguments();

  /**
   * An array of build variables.
   *
   * @return array
   *
   * @see SimpletestBuild::$availableArguments
   */
  public function getBuildVars();

  /**
   * @param array $build_vars
   *
   * @see BuildInterface::getBuildvars
   */
  public function setBuildVars(array $build_vars);

  /**
   * @param string $build_var
   *
   * @return mixed
   *
   * @see BuildInterface::getBuildvars
   */
  public function getBuildVar($build_var);

  /**
   * @param $build_var
   * @param $value
   */
  public function setBuildVar($build_var, $value);

  /**
   * @return \Docker\Docker
   */
  public function getDocker();

  /**
   * Get a list of containers to run Docker exec in.
   *
   * @return array
   *  An array of container IDs. The first key is the type, can be 'php' or
   *  'web'. Web has everything php plus Apache.
   */
  public function getExecContainers();

  public function setExecContainers(array $containers);

  public function startContainer(&$container);

  public function getContainerConfiguration($image = NULL);

  public function startServiceContainerDaemons($type);

  public function getErrorState();

  public function getPlatformDefaults();

  public function getServiceContainers();

  public function setServiceContainers(array $service_containers);

  /**
   * @deprecated
   */
  public function getArtifacts();

  /**
   * @deprecated
   */
  public function setArtifacts(BuildArtifactList $artifacts);

  /**
   * @deprecated
   */
  public function setArtifactDirectory($directory);

  /**
   * Provide the details for build-specific build artifacts.
   *
   * This should be overridden by build-specific classes, to define the build
   * artifacts which should be collected for that class.
   *
   * For Simpletest builds, this includes:
   *   - the xml output from run-tests.sh (if present)
   *   - sqlite database (if present)
   *   - php error log
   *   - apache access log
   *   - apache error log
   *   - any verbose output from run-tests.sh (if present)
   *
   * Syntax:
   *   phpunit results file at ./results.txt:  array('phpunit_results', './results.txt'),
   *   multiple xml files within results/xml directory: array('xml_results', 'results/xml', 'directory'),
   *   a string representing red/blue outcome: array('color', 'red', 'string')
   *   etc.
   */
  public function getBuildArtifacts();

  /**
   * Causes the build to assemble an artifact list.
   */
  public function createArtifactList();

  public function getDefaultDefinitionTemplate($build_type);

  public function generateBuildId();

  public function error();

  public function fail();
}
