<?php
namespace DrupalCI\Build\Environment;

use Pimple\Container;

interface EnvironmentInterface {
  public function inject(Container $container);

  /**
   * {@inheritdoc}
   */
  public function executeCommands($commands);

  public function startExecContainer($container);

  public function startServiceContainerDaemons($container);
}
