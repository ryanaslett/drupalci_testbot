<?php

namespace DrupalCI\Plugin\BuildTask;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTask;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\type;

/**
 * Support cascading config resolution in plugins.
 */
trait BuildTaskTrait {

  /**
   * Build variables service.
   *
   * Your class must set this from the container.
   *
   * @var \DrupalCI\Build\BuildVariablesInterface
   */
  protected $buildVars;

  /**
   * @var float
   */
  protected $startTime;

  /**
   * @var float
   *   Total time taken for this build task, including child tasks
   */
  protected $elapsedTime;

  /**
   * Any variables that can affect the behavior of this plugin, that are
   * specific to this plugin, reside in a configuration array within the plugin.
   *
   * @var array
   *
   */
  protected $configuration;

  /**
   * Configuration overrides passed into the plugin.
   *
   * @var array
   */
  protected $configuration_overrides;

  /**
   * Decorator for run functions to allow all of them to be timed.
   *
   * @param \DrupalCI\Build\BuildInterface $build
   */
  public function start(BuildInterface $build) {
    $this->startTime = microtime(true);
    $this->run($build);
  }

  /**
   * Decorator for complete functions to stop their timer.
   */
  public function finish() {
    $this->complete();
    $elapsed_time = microtime(true) - $this->startTime;
    $this->elapsedTime = $elapsed_time;
  }

  /**
   * @inheritDoc
   */
  public function getElapsedTime($inclusive = TRUE) {
    return $this->elapsedTime;
  }

  protected function override_config() {

    if (!empty($this->configuration_overrides)) {
      if ($invalid_overrides = array_diff_key($this->configuration_overrides, $this->configuration)){
        // @TODO: somebody is trying to override a non-existant configuration value. Throw an exception? print a warning?
      }
      $this->configuration = array_merge($this->configuration, array_intersect_key($this->configuration_overrides, $this->configuration));
    }
  }
}
