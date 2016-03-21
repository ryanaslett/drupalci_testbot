<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Preprocess\definition\SyntaxCheck
 *
 * PreProcesses DCI_SyntaxCheck variable.
 */

namespace DrupalCI\Plugin\Preprocess\definition;

/**
 * @PluginID("showphpversion")
 */
class ShowPHPVersion {

  /**
   * {@inheritdoc}
   *
   * DCI_ShowPHPVersion_Preprocessor
   *
   * If set, add 'php -v' to the execute->command subarray
   *
   * Input format: (bool) $value = "true"
   */
  public function process(array &$definition, $value) {
    if ($value === TRUE || strtolower($value) === 'true') {
      $definition['execute']['command'][] = 'php -v';
    }
    else {
      foreach ($definition['execute']['command'] as $key => $cmd) {
        if ($cmd == 'php -v') {
          unset($definition['execute']['command'][$key]);
        }
      }
    }
  }
}
