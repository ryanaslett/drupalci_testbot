<?php

namespace DrupalCI\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Yaml\Parser;

class YamlServiceProvider implements ServiceProviderInterface {
  /**
   * @inheritDoc
   */
  public function register(Container $container) {
    $container['yaml.parser'] = function ($container) {
      return new Parser();
    };
  }

}
