<?php
namespace DrupalCI\Build\Environment;

use Pimple\Container;

interface EnvironmentInterface {
  public function inject(Container $container);

  /**
   * {@inheritdoc}
   */
  public function executeCommands($commands);

  public function getExecContainers();

  public function setExecContainers(array $containers);

  public function getServiceContainers();

  public function setServiceContainers(array $service_containers);

  public function startServiceContainerDaemons($container_type);

  public function validateImageNames($containers);
}
