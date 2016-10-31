<?php

namespace DrupalCI\Plugin\BuildTask;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildStage\BuildStageInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;

/**
 * Base class for no-op tasks.
 *
 * Sublcass this class to get stub implementations of all the interface methods.
 */
class BuildTaskPlaceholder extends PluginBase  implements BuildStageInterface, BuildTaskInterface  {

  use BuildTaskTrait;

  /**
   * @inheritDoc
   */
  public function configure() {
  }

  /**
   * @inheritDoc
   */
  public function run() {
  }

  /**
   * @inheritDoc
   */
  public function complete() {
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
  }

  /**
   * @inheritDoc
   */
  public function getChildTasks() {
  }

  /**
   * @inheritDoc
   */
  public function setChildTasks($buildTasks) {
  }

  /**
   * @inheritDoc
   */
  public function getShortError() {
  }

  /**
   * @inheritDoc
   */
  public function getErrorDetails() {
  }

  /**
   * @inheritDoc
   */
  public function getResultCode() {
  }

  /**
   * @inheritDoc
   */
  public function getArtifacts() {
  }

}

