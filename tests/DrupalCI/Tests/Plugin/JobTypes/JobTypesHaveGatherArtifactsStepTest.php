<?php

namespace DrupalCI\Tests\Plugin\BuildSteps;

use DrupalCI\Providers\DrupalCIServiceProvider;
use Pimple\Container;
use Symfony\Component\Yaml\Parser;

class JobTypesHaveGatherArtifactsStepTest extends \PHPUnit_Framework_TestCase {

  public function pluginIdProvider() {
    return [
      // Generic does not have a definition file.
      // ['generic'],
      ['phpunit'],
      ['simpletest'],
      ['simpletestlegacy6'],
      ['simpletestlegacy7'],
    ];
  }

  /**
   * @dataProvider pluginIdProvider
   */
  public function testGatherArtifacts($plugin_id) {
    // Create an app.
    $container = new Container();
    $container->register(new DrupalCIServiceProvider());

    // Get the plugin for the plugin ID.
    $manager = $container['plugin.manager.factory']->create('JobTypes');
    $plugin = $manager->getPlugin($plugin_id, $plugin_id);

    // Find the path to the plugin.
    $ref = new \ReflectionClass($plugin);
    $path = dirname($ref->getFileName());

    // Parse the definition file.
    $definition_path = $path . '/drupalci.yml';
    $parser = new Parser();
    $yml_value = $parser->parse(file_get_contents($definition_path), TRUE, FALSE);

    // Verify that the file contains publish:gather_artifacts.
    $this->assertArrayHasKey('publish', $yml_value);
    $this->assertArrayHasKey('gather_artifacts', $yml_value['publish']);
    $this->assertInternalType('string', $yml_value['publish']['gather_artifacts']);
  }

}
