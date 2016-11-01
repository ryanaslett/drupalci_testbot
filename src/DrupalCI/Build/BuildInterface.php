<?php
/**
 * @file
 * Contains
 */
namespace DrupalCI\Build;

use DrupalCI\Build\Codebase\Codebase;
use Symfony\Component\Console\Output\OutputInterface;

interface BuildInterface {

  /**
   * @return string
   */
  public function getBuildType();

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
   * @param string
   */
  public function generateBuild($arg);

  /**
   * Executes a configured build.
   *
   * @return mixed
   */
  public function executeBuild();

  /**
   * This is the directory where we place everything specific to this build
   * The primary exception of something that is needed that does not live
   * under the build directory is the Database.
   *
   * @return mixed
   */
  public function getBuildDirectory();

  /**
   * This is the directory where we place all of our artifacts.
   *
   * @return mixed
   */
  public function getArtifactDirectory();

  /**
   * This is the directory where we place artifacts that can be parsed
   * by jenkins xml parsing. It is usually located *under* the artifacts
   * directory
   *
   * @return mixed
   */
  public function getXmlDirectory();

  /**
   * This is where we checkout the code to. It should be volume mounted over
   * to /var/www/html inside the docker containers.
   *
   * @return mixed
   */
  public function getSourceDirectory();


  public function generateBuildId();
}
