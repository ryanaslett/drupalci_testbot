<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Preprocess\definition\SyntaxCheck
 *
 * PreProcesses DCI_SyntaxCheck variable.
 */

namespace DrupalCI\Plugin\Preprocess\definition;

/**
 * @PluginID("simpletestjs")
 */
class RunSimpletestJSTests {

  /**
   * {@inheritdoc}
   *
   * DCI_RunSimpletestJSTests_Preprocessor
   *
   * If set, enable simpletest js testing.
   *
   * Input format: (bool) $value = TRUE | "true"
   */
  public function process(array &$definition, $value) {
    if ($value === TRUE || strtolower($value) === "true") {
      $js_commands = [
        "daemon -- nohup /usr/bin/phantomjs %DCI_SimpletestJSOptions%",
        "sleep 5"
      ];
      // Make sure our daemon commands run first.
      $existing = $definition['execute']['command'];
      $definition['execute']['command'] = array_merge($js_commands, $existing);
      // Make sure the simpletest JS command runs last
      // $definition['execute']['command'][] = "PUT RUN-TESTS EXECUTION HERE WITH %DCI_RunTestsJSOptions% placeholder';
    }
  }
}
