<?php

namespace DrupalCI;

use Pimple\Container;

/**
 * Provide a way to inject the container.
 *
 * Objects implementing the Injectable interface should gather only the
 * dependencies they actually need and not use this trait.
 *
 * @see \DrupalCI\Injectable
 */
trait InjectableTrait {

  /**
   * @var \Pimple\Container
   */
  protected $container;

  public function setContainer(Container $container) {
    $this->container = $container;
//    error_log('setting container for ' . get_class($this));
  }

}

