<?php

namespace DrupalCI\Plugin;

use Pimple\Container;

class PluginManagerFactory {
  protected $container;

  public function __construct(Container $c) {
    $this->container = $c;
  }

  public function create($plugin_type) {
    return new PluginManager($plugin_type, $this->container);
  }
}
