<?php

namespace DrupalCI\Providers;

use DrupalCI\Console\DrupalCIConsoleApp;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Registers application-level services.
 */
class DrupalCIServiceProvider implements ServiceProviderInterface {

  /**
    * Register all our app-level services.
    *
    * @param Container $container
    */
  public function register(Container $container) {
    $container['console'] = function ($container) {
      return new DrupalCIConsoleApp('DrupalCI - CommandLine', '0.2', $container);
    };
  }
}

