<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("composer")
 */
class Composer extends PluginBase implements BuildStepInterface, BuildTaskInterface {

  /**
   * The current build.
   *
   * @var \DrupalCI\Build\BuildInterface
   */
  protected $build;


  public function inject(Container $container) {
    parent::inject($container);
    $this->build = $container['build'];
  }

  /**
   * @inheritDoc
   */
  public function run() {

    $source_dir = $this->build->getSourceDirectory();

    $cmd = "./bin/composer " . $this->configuration['options'] . " " . $source_dir;
    $this->exec($cmd, $cmdoutput, $result);

  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'options' => 'install --prefer-dist --working-dir',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getArtifacts() {
    // TODO: Implement getArtifacts() method.
  }


}
