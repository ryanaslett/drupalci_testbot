<?php

namespace DrupalCI;

use DrupalCI\Console\DrupalCIConsoleApp;
use DrupalCI\Plugin\PluginManagerFactory;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Registers application-level services.
 */
class DrupalCIServiceProvider implements ServiceProviderInterface {

  /**
   * Register all our app-level services.
   *
   * @param Container $c
   */
  public function register(Container $c) {
    $c['console'] = function ($c) {
      return new DrupalCIConsoleApp('DrupalCI - CommandLine', '0.1', $c);
    };
    $c['console.helpers'] = function ($c) {
      return $c['console']->getHelpers();
    };
    $c['plugin.manager.factory'] = function ($c) {
      return new PluginManagerFactory($c);
    };
  }

}
