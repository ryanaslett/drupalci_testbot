<?php
use DrupalCI\Plugin\Preprocess\definition\SyntaxCheck;

/**
 * @file
 * contains \DrupalCI\Tests\Plugin\Preprocess\definition\SyntaxCheckDefinitionPreprocessorTest
 *
 * @group Plugin
 * @group DefinitionPreprocessor
 */

class SyntaxCheckDefinitionPreprocessorTest extends \PHPUnit_Framework_TestCase
{
  /**
   * @param array $definition        The definition array being processed
   * @param boolean $value     Whether Syntax Check should be enabled
   * @param array $expected_result   Expected Result
   *
   * @dataProvider provideSyntaxCheckDefinitionPreprocessorInputDefinitions
   */
  public function testSyntaxCheckDefinitionPreprocessor($definition, $value, $expected_result) {
    // Adds $definition['setup']['syntaxcheck'] = [TRUE|FALSE] line to the definition array.
    // SyntaxCheck should be the last element of the array after processing.

    $plugin = new SyntaxCheck();
    $plugin->process($definition, $value);
    $this->assertEquals($expected_result, $definition);
  }

  public function provideSyntaxCheckDefinitionPreprocessorInputDefinitions() {
    return [
      // Test enabling syntaxcheck
      [[], true, ['setup' => ['syntaxcheck' => "TRUE"]]],
      // Test disabling syntaxcheck
      [[], false, ['setup' => ['syntaxcheck' => "FALSE"]]],
      // Test that syntaxcheck is the last element of the setup array after processing
      [['setup' => ['syntaxcheck' => "TRUE", 'otherkey' => 'otherkeyvalue']], true, ['setup' => ['otherkey' => 'otherkeyvalue', 'syntaxcheck' => "TRUE"]]],
      // Prove that changing the key order results in a different array
      [['setup' => ['syntaxcheck' => "TRUE", 'otherkey' => 'otherkeyvalue']], true, ['setup' => ['syntaxcheck' => "TRUE", 'otherkey' => 'otherkeyvalue']]],
    ];
  }
}