<?php

namespace DrupalCI\Plugin;

use DrupalCI\Build\BuildInterface;

/**
 * Interface BuildTaskInterface
 *
 * @package Plugin
 */
interface BuildTaskInterface {

  public function run(BuildInterface $job, $data);

  /**
   * Gives a list of default values for variables for this task.
   *
   * @return array
   *   An array with keys being DCI_* variables, and values being default values
   *   for those variables. Use an empty string to specify no default value,but
   *   to declare a DCI_* variable.
   */
  public function getDefaultConfiguration();

/*
 * @todo: All these.
  public function getResultCode();
  public function getResultString();
  public function getResult();
  public function getArtifacts();
  public function getConfigurableVariables();
  public function getElapsedTime();
 */

}
