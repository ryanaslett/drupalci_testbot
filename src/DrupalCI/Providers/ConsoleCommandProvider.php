<?php

namespace DrupalCI\Providers;

use DrupalCI\Console\Command\Docker\DockerBuildCommand;
use DrupalCI\Console\Command\Docker\DockerRemoveCommand;
use DrupalCI\Console\Command\Init\InitAllCommand;
use DrupalCI\Console\Command\Init\InitBaseContainersCommand;
use DrupalCI\Console\Command\Init\InitDatabaseContainersCommand;
use DrupalCI\Console\Command\Init\InitDependenciesCommand;
use DrupalCI\Console\Command\Init\InitDockerCommand;
use DrupalCI\Console\Command\Init\InitWebContainersCommand;
use DrupalCI\Console\Command\Docker\DockerPullCommand;
use DrupalCI\Console\Command\Run\RunCommand;
use DrupalCI\Console\Command\Status\StatusCommand;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Command service provider for all our CLI commands.
 *
 * Note that this provider requires the existence of console.input and
 * console.output, so it must be registered after those services are available.
 */
class ConsoleCommandProvider implements ServiceProviderInterface {

  /**
   * Register all our console commands.
   *
   * @param Container $container
   */
  public function register(Container $container)
  {
    // Console Commands
    $container['command.status'] = function ($container) {
      return new StatusCommand();
    };
    $container['command.pull'] = function ($container) {
      return new DockerPullCommand();
    };
    $container['command.docker.remove'] = function ($container) {
      return new DockerRemoveCommand();
    };
    $container['command.init.all'] = function ($container) {
      return new InitAllCommand();
    };
    $container['command.init.base'] = function ($container) {
      return new InitBaseContainersCommand();
    };
    $container['command.init.db'] = function ($container) {
      return new InitDatabaseContainersCommand();
    };
    $container['command.init.dependencies'] = function ($container) {
      return new InitDependenciesCommand();
    };
    $container['command.init.docker'] = function ($container) {
      return new InitDockerCommand();
    };
    $container['command.init.web'] = function ($container) {
      return new InitWebContainersCommand();
    };
    $container['command.run'] = function ($container) {
      return new RunCommand();
    };

    $container['commands'] = function ($container) {
      return array(
        $container['command.status'],
        $container['command.pull'],
        $container['command.docker.remove'],
        $container['command.init.all'],
        $container['command.init.base'],
        $container['command.init.db'],
        $container['command.init.dependencies'],
        $container['command.init.docker'],
        $container['command.init.web'],
        $container['command.run']
      );
    };
  }

}
