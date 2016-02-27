<?php

namespace DrupalCI\Providers;

use DrupalCI\Console\Command\BuildCommand;
use DrupalCI\Console\Command\Config\ConfigClearCommand;
use DrupalCI\Console\Command\Config\ConfigListCommand;
use DrupalCI\Console\Command\Config\ConfigLoadCommand;
use DrupalCI\Console\Command\Config\ConfigResetCommand;
use DrupalCI\Console\Command\Config\ConfigSaveCommand;
use DrupalCI\Console\Command\Config\ConfigSetCommand;
use DrupalCI\Console\Command\Config\ConfigShowCommand;
use DrupalCI\Console\Command\DockerRemoveCommand;
use DrupalCI\Console\Command\Init\InitAllCommand;
use DrupalCI\Console\Command\Init\InitBaseContainersCommand;
use DrupalCI\Console\Command\Init\InitConfigCommand;
use DrupalCI\Console\Command\Init\InitDatabaseContainersCommand;
use DrupalCI\Console\Command\Init\InitDependenciesCommand;
use DrupalCI\Console\Command\Init\InitDockerCommand;
use DrupalCI\Console\Command\Init\InitPhpContainersCommand;
use DrupalCI\Console\Command\Init\InitWebContainersCommand;
use DrupalCI\Console\Command\PullCommand;
use DrupalCI\Console\Command\RunCommand;
use DrupalCI\Console\Command\Status\StatusCommand;
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
    * @param Container $container
    */
  public function register(Container $container) {
    $container['console'] = function ($container) {
      return new DrupalCIConsoleApp('DrupalCI - CommandLine', '0.2', $container);
    };
    $container['plugin.manager.factory'] = function ($container) {
      return new PluginManagerFactory($container);
    };
  }

}
