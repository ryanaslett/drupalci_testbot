<?php

/**
 * @file
 * Console application for Drupal CI.
 */

namespace DrupalCI\Console;

use DrupalCI\InjectableTrait;
use Pimple\Container;
use Symfony\Component\Console\Application;
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
