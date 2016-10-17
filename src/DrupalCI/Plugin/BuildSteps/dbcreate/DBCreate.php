<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\dbcreate\MariaDB.
 */

namespace DrupalCI\Plugin\BuildSteps\dbcreate;

use DrupalCI\Injectable;
use DrupalCI\Plugin\PluginBase;
use Pimple\Container;
use DrupalCI\Build\BuildInterface;

/**
 * @PluginID("dbcreate")
 */
class DBBase extends PluginBase implements Injectable  {
  /* @var $database \DrupalCI\Build\Environment\DatabaseInterface */
  protected $database;

  /**
   * @inheritDoc
   */
  public function setContainer(Container $container) {
    /* @var \DrupalCI\Build\Environment\DatabaseInterface */
    $this->database = $container['db.system'];
  }

  public function run(BuildInterface $build, $data){
    // @TODO find a better way to stop checking for sqlite database everywhere.
    if ($this->database->getDbType() !== 'sqlite') {
      $this->database->createDB();
    }
  }
}
