<?php

namespace DrupalCI\Plugin\BuildTask\BuildStage;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildStage\BuildStageInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("codebase")
 */

class CodebaseBuildStage extends PluginBase  implements BuildStageInterface, BuildTaskInterface  {

  use BuildTaskTrait;
  /**
   * @inheritDoc
   */
  public function configure() {
    // TODO: Implement configure() method.
  }

  /**
   * @inheritDoc
   */
  public function run() {
    // TODO: Implement run() method.
  }

  /**
   * @inheritDoc
   */
  public function complete() {
    // TODO: Implement complete() method.
  }

  /**
   * @inheritDoc
   */
  public function getChildTasks() {
    // TODO: Implement getChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function setChildTasks($buildTasks) {
    // TODO: Implement setChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function getShortError() {
    // TODO: Implement getShortError() method.
  }

  /**
   * @inheritDoc
   */
  public function getErrorDetails() {
    // TODO: Implement getErrorDetails() method.
  }

  /**
   * @inheritDoc
   */
  public function getArtifacts() {
    // TODO: Implement getArtifacts() method.
  }

}
