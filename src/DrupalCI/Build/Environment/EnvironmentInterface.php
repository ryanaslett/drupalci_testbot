<?php
namespace DrupalCI\Build\Environment;

use Pimple\Container;

interface EnvironmentInterface {
  public function inject(Container $container);

  /**
   * {@inheritdoc}
   */
  public function executeCommands($commands);

  public function getExecContainer();

  public function setExecContainer($container);

  public function startExecContainer($container);

  public function setServiceContainer($container);

  public function startServiceContainerDaemons($container);

  public function validateImageName($containers);
}
