<?php

namespace DrupalCI\Plugin\BuildSteps;

use DrupalCI\Plugin\JobTypes\JobInterface;

/**
 * Promises methods for all build step classes.
 */
interface BuildStepInterface {

  /**
   * Execute the build step.
   *
   * @param JobInterface $job
   *   The job this build step belongs to.
   * @param type $data
   *   The data specified in the job definition file or DCI_* variables.
   */
  public function run(JobInterface $job, $data);

}
