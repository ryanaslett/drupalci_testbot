<?php

/**
 * @file
 * Console application for Drupal CI.
 */

namespace DrupalCI\Console;

use DrupalCI\Console\Command\Init\InitBaseContainersCommand;
use DrupalCI\Console\Command\Init\InitDatabaseContainersCommand;
use DrupalCI\Console\Command\Init\InitDependenciesCommand;
use DrupalCI\Console\Command\Init\InitDockerCommand;
use DrupalCI\Console\Command\Init\InitWebContainersCommand;
use DrupalCI\Console\Command\Init\InitPhpContainersCommand;
use DrupalCI\InjectableTrait;
use Pimple\Container;
use Symfony\Component\Console\Application;
use DrupalCI\Console\Command\Init\InitAllCommand;
use DrupalCI\Console\Command\Docker\DockerBuildCommand;
use DrupalCI\Console\Command\Docker\DockerPullCommand;
use DrupalCI\Console\Command\Docker\DockerRemoveCommand;
use DrupalCI\Console\Command\Run\RunCommand;
use DrupalCI\Console\Command\Status\StatusCommand;
use DrupalCI\Providers\ConsoleCommandProvider;

class DrupalCIConsoleApp extends Application {

  use InjectableTrait;

  /**
   * Constructor.
   *
   * We'll store the injected container so that code with access to the app can
   * access it as needed.
   */
  public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN', Container $container) {
    parent::__construct($name, $version);
    $this->container = $container;
    $container->register(new ConsoleCommandProvider());
    $this->addCommands($container['commands']);
  }

  /**
   * Access the application object's container.
   *
   * @return \Pimple\Container
   */
  public function getContainer() {
    return $this->container;
  }

}
