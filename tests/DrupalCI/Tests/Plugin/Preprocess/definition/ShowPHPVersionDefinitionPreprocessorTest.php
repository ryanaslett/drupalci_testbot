<?php
use DrupalCI\Plugin\Preprocess\definition\ShowPHPVersion;
use DrupalCI\Tests\Plugin\Preprocess\definition\DefinitionPreprocessorTestBase;

/**
 * @file
 * contains \DrupalCI\Tests\Plugin\Preprocess\definition\ShowPHPVersionDefinitionPreprocessorTest
 *
 * @group Plugin
 * @group DefinitionPreprocessor
 */

class ShowPHPVersionDefinitionPreprocessorTest extends DefinitionPreprocessorTestBase
{
  /**
   * @param boolean $value                    Whether the php version should be shown
   * @param array $definition_overrides       An array of overrides to merge into the definition template before processing
   * @param array $expected_result            Expected Result
   *
   * @dataProvider provideShowPHPVersionDefinitionPreprocessorInputDefinitions
   */
  public function testShowPHPVersionDefinitionPreprocessor($value, $definition_overrides, $expected_result) {
    // Adds $definition['execute']['command'][] = php -v line to the definition array.

    $definition = $this->getDefinitionTemplate();
    $definition = array_merge($definition, $definition_overrides);

    $plugin = new ShowPHPVersion();
    $plugin->process($definition, $value);
    $this->assertEquals($expected_result, $definition['execute']['command']);
  }

  public function provideShowPHPVersionDefinitionPreprocessorInputDefinitions() {
    return [
      // Test enabling php -v
      ['true', [], ['my_command', 'php -v']],
      ['True', [], ['my_command', 'php -v']],
      [TRUE, [], ['my_command', 'php -v']],
      // Test disabling php -v
      ['false', [], ['my_command']],
      ['False', [], ['my_command']],
      [FALSE, [], ['my_command']],
      ['abcde', [], ['my_command']],
      ['false', ['execute' => ['command' => ['my_command', 'php -v']]], ['my_command']],
      ['False', ['execute' => ['command' => ['my_command', 'php -v']]], ['my_command']],
      [FALSE, ['execute' => ['command' => ['my_command', 'php -v']]], ['my_command']],
      ['abcde', ['execute' => ['command' => ['my_command', 'php -v']]], ['my_command']],
    ];
  }
}