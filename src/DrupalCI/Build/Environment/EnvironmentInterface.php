<?php
namespace DrupalCI\Build\Environment;

interface EnvironmentInterface {
  public function inject(Container $container);

  /**
   * {@inheritdoc}
   */
  public function executeCommands($data);

  /**
   * @return \Docker\Docker
   */
  public function getDocker();

  public function getExecContainers();

  public function setExecContainers(array $containers);

  public function startContainer(&$container);

  public function getContainerConfiguration($image = NULL);

  public function getServiceContainers();

  public function setServiceContainers(array $service_containers);

  public function startServiceContainerDaemons($container_type);

  public function validateImageNames($containers);
}
