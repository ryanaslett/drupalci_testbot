<?php
use DrupalCI\Plugin\Preprocess\definition\RunSimpletestJSTests;
use DrupalCI\Tests\Plugin\Preprocess\definition\DefinitionPreprocessorTestBase;

/**
 * @file
 * contains \DrupalCI\Tests\Plugin\Preprocess\definition\RunSimpletestJSTestsDefinitionPreprocessorTest
 *
 * @group Plugin
 * @group DefinitionPreprocessor
 */

class RunSimpletestJSTestsDefinitionPreprocessorTest extends DefinitionPreprocessorTestBase
{
  /**
   * @param boolean $value                    Whether the php version should be shown
   * @param array $expected_result            Expected Result
   *
   * @dataProvider provideShowPHPVersionDefinitionPreprocessorInputDefinitions
   */
  public function testRunSimpletestJSTestsDefinitionPreprocessor($value, $expected_result) {
    // Adds Simpletest Javascript testing elements to the definition template
    $definition = $this->getDefinitionTemplate();

    $plugin = new RunSimpletestJSTests();
    $plugin->process($definition, $value);
    $this->assertEquals($expected_result, $definition['execute']['command']);
  }

  public function provideShowPHPVersionDefinitionPreprocessorInputDefinitions() {
    $expected_enabled = [
      "daemon -- nohup /usr/bin/phantomjs %DCI_SimpletestJSOptions%",
      "sleep 5",
      "my_command",
    ];

    return [
      // Test enabling php -v
      ['true', $expected_enabled],
      ['True', $expected_enabled],
      [TRUE, $expected_enabled],
      // Test disabling php -v
      ['false', ['my_command']],
      ['False', ['my_command']],
      [FALSE, ['my_command']],
      ['abcde', ['my_command']],
    ];
  }
}