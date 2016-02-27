<?php

namespace DrupalCI\Providers;


use Docker\Docker;
use Docker\Http\DockerClient;
use Docker\Manager\ImageManager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class DockerServiceProvider implements ServiceProviderInterface{

  /**
   * Register all of our Docker managers.
   *
   * @param Container $container
   */
  public function register(Container $container) {

    // Docker Managers
    $container['docker'] = function ($container) {
      return new Docker(DockerClient::createWithEnv());
    };

    // httpClient
    $container['docker.client'] = function ($container) {
      return $container['docker']->getClient();
    };

    // Docker Image Manager
    $container['docker.image.manager'] = function ($container) {
      return $container['docker']->getImageManager();
    };
  }

}