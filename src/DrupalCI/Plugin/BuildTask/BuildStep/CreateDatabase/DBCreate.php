<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CreateDatabase;


use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("dbcreate")
 */
class DBCreate extends PluginBase implements BuildStepInterface, BuildTaskInterface, Injectable {

  use BuildTaskTrait;
  /**
   * @var \DrupalCI\Build\Environment\DatabaseInterface
   */
  protected $database;

  public function inject(Container $container) {
    $this->database = $container['db.system'];
  }

  /**
   * @inheritDoc
   */
  public function configure() {


  }

  /**
   * @inheritDoc
   */
  public function run() {

    if ($this->database->getDbType() !== 'sqlite') {
      $this->database->createDB();
    }
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
    return [];
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
