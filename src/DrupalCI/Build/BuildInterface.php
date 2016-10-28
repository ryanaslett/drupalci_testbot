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
   * @return \DrupalCI\Build\Codebase\Codebase
   */
  public function getCodebase();

  /**
   * @param \DrupalCI\Build\Codebase\Codebase $codebase
   */
  public function setCodebase(Codebase $codebase);

  /**
   * @return mixed
   */
  public function getBuildDirectory();

  public function generateBuildId();
}
