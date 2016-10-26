<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildPhase\BuildPhaseInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("create_db")
 */
class CreateDatabaseBuildPhase extends PluginBase implements BuildPhaseInterface, BuildTaskInterface {

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
