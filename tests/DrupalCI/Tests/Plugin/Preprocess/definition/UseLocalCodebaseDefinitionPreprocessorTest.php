<?php
use DrupalCI\Plugin\Preprocess\definition\UseLocalCodebase;

/**
 * @file
 * contains \DrupalCI\Tests\Plugin\Preprocess\definition\UseLocalCodebaseDefinitionPreprocessorTest
 *
 * @group Plugin
 * @group DefinitionPreprocessor
 */

class UseLocalCodebaseDefinitionPreprocessorTest extends \PHPUnit_Framework_TestCase
{
  public function testUseLocalCodebaseDefinitionPreprocessor() {
    $definition = [
      'setup' => [
        'checkout' => [
          'protocol' => 'git',
          'repo' => 'git://my.repo/myrepo.git'
        ]
      ]
    ];
    $source_directory = 'my/local/directory';
    $dci_variables = [];
    $expected_result = [
      'setup' => [
        'checkout' => [
          'protocol' => 'local',
          'source_dir' => 'my/local/directory'
        ]
      ]
    ];

    $plugin = new UseLocalCodebase();
    $plugin->process($definition, $source_directory, $dci_variables);
    $this->assertEquals($expected_result, $definition);
   }
}