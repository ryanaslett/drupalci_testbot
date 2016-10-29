<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("composer")
 */
class Composer extends PluginBase implements BuildStepInterface, BuildTaskInterface {

  use BuildTaskTrait;

  /* @var \DrupalCI\Build\BuildInterface */
  protected $build;


  public function inject(Container $container) {
    parent::inject($container);
    $this->build = $container['build'];
  }

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

    // ENVIRONMENT - Source Directory
    $source_dir = $this->build->getSourceDirectory();

    $cmd = "./bin/composer " . $this->configuration['options'] . " " . $source_dir;
    $this->exec($cmd, $cmdoutput, $result);

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
    return [
      'options' => 'install --prefer-dist --working-dir',
    ];
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
