<?php

namespace DrupalCI\Plugin\BuildTask;

/**
 * Interface BuildTaskInterface
 *
 * @package Plugin
 */
interface BuildTaskInterface {
  /**
   * Prepares this task to run.
   *
   *   An array of configuration for this build task determined by the following
   *   precedence ordering:
   *     1. Default values for this task.
   *     2. Any Environment Variables that should override the defaults
   *     3. Any command line settings that should override the defaults
   *     4. Any passed in overrides that are provided from the build.yml
   */
  public function configure();

  /**
   * Decorator for run functions to allow all of them to be timed.
   */
  public function start();

  /**
   *   The build override configuration as defined in the yml file.
   *
   * @return int
   *   returns the status code of this BuildTask's execution. 0 = pass,
   *   1 = fail, and 2 = exception.  Note that if this BuildTask needs to halt
   *   execution of the build, it should throw a BuildTaskException rather than
   *   return a 2.
   */
  public function run();

  /**
   * Decorator for complete functions to stop their timer.
   */
  public function finish();

  /**
   * Called when a Task and all of its children have finished processing.
   */
  public function complete();

  /**
   * @param boolean $inclusive
   *   If true, will return the total elasped time for this task and all of its
   *   chilren.  If false, will return the elapsed time for this task, minus
   *   the time of its children.
   *
   * @return float
   *
   *   Returns the time seconds.microseconds
   */
  public function getElapsedTime($inclusive);

  /**
   * Gives a list of default values for variables for this task.
   *
   * @return array
   *   An array of configuration that this buildtask can accept. Used primarily
   *   to generate a build template for discoverability.
   */
  public function getDefaultConfiguration();

  /**
   * @return array
   *   This returns any child tasks as strings.
   */
  public function getChildTasks();

  /**
   * @param $buildTasks
   *
   *   Sets the subordinate Tasks on this Task
   */
  public function setChildTasks($buildTasks);

  /**
   * @return string
   *   This is a short error string to describe the failure
   */
  public function getShortError();

  /**
   * @return string
   *   Returns the full error details/exception/error message when a BuildTask
   *   encounters an error.
   */
  public function getErrorDetails();

  /**
   * @return int
   *   Returns the integer status code of this BuildTask (0 = success, 1 =
   *   failure, 2 = exception, > 2 is abnormal)
   */
  public function getResultCode();

  /**
   * @return array
   *
   *   Returns an array of artifact paths that this buildtask creates.
   *   The build should copy and rename these to an overall build artifact
   *   location.
   */
  public function getArtifacts();

  /* TODO: each task should be able to define their own command line switches
   * that override config like the environment variables do.
   * public function getCLIHelp();
   *
   * TODO: each task should be able to display their configurable values and
   * we should use that to help with discovery or something.
   * public function getConfigurables();
   */

}
