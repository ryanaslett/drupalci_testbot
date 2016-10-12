<?php
/**
 * @file
 * Contains
 */
namespace DrupalCI\Build;

use DrupalCI\Build\Codebase\CodeBase;
use DrupalCI\Build\Definition\BuildDefinition;
use DrupalCI\Build\Results\BuildResults;
use Symfony\Component\Console\Output\OutputInterface;

interface BuildInterface {

  /**
   * @return string
   */
  public function getJobType();

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
  public function getJobDefinition();

  /**
   * @param \DrupalCI\Build\Definition\BuildDefinition $job_definition
   */
  public function setJobDefinition(BuildDefinition $job_definition);

  /**
   * @return \DrupalCI\Build\Codebase\CodeBase
   */
  public function getJobCodebase();

  /**
   * @param \DrupalCI\Build\Codebase\CodeBase $job_codebase
   */
  public function setJobCodebase(CodeBase $job_codebase);

  /**
   * @return \DrupalCI\Build\Results\BuildResults
   */
  public function getJobResults();

  /**
   * @param \DrupalCI\Build\Results\BuildResults $job_results
   */
  public function setJobResults(BuildResults $job_results);


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
   * @see JobInterface::getBuildvars
   */
  public function setBuildVars(array $build_vars);

  /**
   * @param string $build_var
   *
   * @return mixed
   *
   * @see JobInterface::getBuildvars
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

  public function getArtifacts();

  public function setArtifacts($artifacts);

  public function setArtifactDirectory($directory);

  public function getDefaultDefinitionTemplate($job_type);

  public function generateBuildId();

  public function error();

  public function fail();
}
