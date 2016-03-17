<?php
use DrupalCI\Plugin\Preprocess\definition\JunitXml;

/**
 * @file
 * contains \DrupalCI\Tests\Plugin\Preprocess\definition\JUnitXMLDefinitionPreprocessorTest
 *
 * @group Plugin
 * @group DefinitionPreprocessor
 */

class JUnitXMLDefinitionPreprocessorTest extends \PHPUnit_Framework_TestCase
{
  public function testJUnitXMLDefinitionPreprocessor() {
    $definition = $this->getDefinition();
    $target_directory = 'my/target/directory';
    $dci_variables = [];

    $plugin = new JUnitXML();
    $plugin->process($definition, $target_directory, $dci_variables);
    $this->assertEquals($target_directory, $definition['publish']['junit_xmlformat']);
  }

  protected function getDefinition()
  {
    return [
      'environment' => [
        'db' => [
          '%DCI_DBVersion%'
        ],
        'web' => [
          '%DCI_PHPVersion%'
        ],
      ],
      'setup' => [
        'checkout' => [
          'protocol' => 'git',
          'repo' => '%DCI_CoreRepository%',
          'branch' => '%DCI_CoreBranch%',
          'depth' => '%DCI_GitCheckoutDepth%',
          'checkout_dir' => '.',
          'commit_hash' => '%DCI_GitCommitHash%',
        ],
        'mkdir' => [
          'my_directory',
        ],
        'command' => [
          'my_command',
        ],
      ],
      'install' => [
      ],
      'execute' => [
        'command' => [
          'my_command',
        ],
        'testcommand' => [
          'my_test_command',
        ],
      ],
      'publish' => [
        'gather_artifacts' => '/var/www/html/artifacts',
      ],
    ];
  }
}