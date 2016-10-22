<?php

namespace DrupalCI\Providers;


use DrupalCI\Build\Definition\BuildDefinition;
use DrupalCI\Build\BuildVariables;
use DrupalCI\Console\DrupalCIConsoleApp;
use DrupalCI\Plugin\PluginManagerFactory;
use DrupalCI\Providers\DockerServiceProvider;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use DrupalCI\Providers\DatabaseServiceProvider;

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
    $container->register(new DockerServiceProvider());
    $container->register(new DatabaseServiceProvider());
    $container['console'] = function ($container) {
      $console = new DrupalCIConsoleApp('DrupalCI - CommandLine', '0.2');
      $console->inject($container);
      return $console;
    };
    $container['plugin.manager.factory'] = function ($container) {
      return new PluginManagerFactory($container);
    };
    $container['build.vars'] = function ($container) {
      return new BuildVariables($container['plugin.manager.factory']->create('Preprocess'));
    };
    // @TODO: This may be entirely unnecessary or duplicates the above. Will see
    // after the merge.
    $container['build.definition'] = function ($container) {
      return new BuildDefinition();
    };
  }

}
