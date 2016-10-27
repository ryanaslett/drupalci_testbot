<?php
/**
 * @file
 * Contains
 */
namespace DrupalCI\Build;

use DrupalCI\Build\Codebase\Codebase;
use DrupalCI\Build\Definition\BuildDefinition;
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
   * @return string
   *
   * The filename that was originally used to define this build.
   */
  public function getBuildFile();

  /**
   * @param $buildfile
   */
  public function setBuildFile($buildfile);
  /**
   * @param string
   */
  public function generateBuild($arg);

  /**
   * @return \DrupalCI\Build\Codebase\Codebase
   */
  // CODEBASE
  public function getCodebase();

  /**
   * @param \DrupalCI\Build\Codebase\Codebase $codebase
   */
  // CODEBASE
  public function setCodebase(Codebase $codebase);

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

  public function getServiceContainers();

  public function setServiceContainers(array $service_containers);

  public function getDefaultDefinitionTemplate($build_type);

  public function generateBuildId();
}
