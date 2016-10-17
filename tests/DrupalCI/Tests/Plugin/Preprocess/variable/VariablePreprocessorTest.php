<?php

 namespace DrupalCI\Tests\Plugin\Preprocess\variable;

use DrupalCI\Plugin\Preprocess\VariableInterface;

class VariablePreprocessorTest extends \PHPUnit_Framework_TestCase
{

  /**
   * @param string $plugin_name        Name of the plugin being tested
   * @param string $dci_variable       Original value of the target variable
   * @param string $plugin_input       Value passed into the plugin logic
   * @param array  $expected return    Expected return value of the $plugin->process() method
   *
   * @dataProvider provideBasicVariablePreprocessorInputs
   *
   * @return array
   */
  public function testBasicVariablePreprocessors($plugin_name, $dci_variable, $plugin_input, $expected_return) {
    $plugin = "\\DrupalCI\\Plugin\\Preprocess\\variable\\$plugin_name";
    /** @var VariableInterface $instance */
    $instance = new $plugin();
    $return_value = $instance->process($dci_variable, $plugin_input);
    $this->assertEquals($expected_return, $return_value);
  }

  public function provideBasicVariablePreprocessorInputs() {
    return [
      // DCI_TestItem
      ['TestItem', 'abc', '', 'abc'],
      ['TestItem', 'abc', 'all', '--all'],
      ['TestItem', 'abc', 'module:mymodule', '--module mymodule'],
      ['TestItem', 'abc', 'class:myclass', '--class myclass'],
      ['TestItem', 'abc', 'file:myfile', '--file myfile'],
      ['TestItem', 'abc', 'directory:mydir', '--directory mydir'],
      ['TestItem', 'abc', 'invalid:entry', 'abc'],
    ];
  }


}