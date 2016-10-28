<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\StartContainers;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * @PluginID("docker_compose")
 */
class DockerCompose extends PluginBase implements BuildStepInterface, BuildTaskInterface {

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

  public function getShortError() {
    // TODO: Implement getShortError() method.
  }

  public function getErrorDetails() {
    // TODO: Implement getErrorDetails() method.
  }

  public function getResultCode() {
    // TODO: Implement getResultCode() method.
  }

  public function getArtifacts() {
    // TODO: Implement getArtifacts() method.
  }

}
