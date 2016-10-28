<?php

namespace DrupalCI\Plugin\BuildTask\BuildStage;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildStage\BuildStageInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("codebase")
 */

class CodebaseBuildStage extends PluginBase  implements BuildStageInterface, BuildTaskInterface  {

  use BuildTaskTrait;
  // VARS DCI_WorkingDir
  //  DCI_CoreProject
  //  DCI_CoreVersion
  //  DCI_CoreBranch
  //  DCI_BuildId
  /**
   * @inheritDoc
   */
  public function configure() {
    // TODO: Implement configure() method.
  }

  /**
   * @inheritDoc
   */
  public function run(BuildInterface $build) {
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
  public function getDefaultConfiguration() {
    // TODO: Implement getDefaultConfiguration() method.
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
  public function getResultCode() {
    // TODO: Implement getResultCode() method.
  }

  /**
   * @inheritDoc
   */
  public function getArtifacts() {
    // TODO: Implement getArtifacts() method.
  }

}
