<?php

namespace DrupalCI\Plugin;

use DrupalCI\Plugin\BuildTaskInterface;

/**
 * Support cascading config resolution in plugins.
 */
trait BuildTaskTrait {

  /**
   * Performs substitution on DCI_* variables within config.
   *
   * Substitution occurs on all values for the given array. DCI_* variables are
   * marked as %DCI_Variable% and can occur within a string.
   *
   * @param array $config
   *   Config from the build definition, for this plugin.
   *
   * @return array
   *   Config with substitutions provided for %DCI_Variables%.
   */
  protected function resolveDciVariables($config) {
    if ($this instanceof BuildTaskInterface) {
      // Use $this->dciReplace() as a callback for walking the array.
      array_walk_recursive($config, [$this, 'dciReplace'], $this->getDefaultConfiguration());
    }
    return $config;
  }

  /**
   * Callback for array_walk_recursive().
   *
   * Replaces %DCI_*% variables in place.
   *
   * @param type &$value
   *   The value where we'll resolve DCI variables.
   * @param type $key
   *   Unused.
   * @param type $dci_defaults
   *   The DCI defaults for this class.
   */
  private function dciReplace(&$value, $key, $dci_defaults) {
    if (preg_match_all("/%(.*?)%/", $value, $match)) {
      foreach ($match[1] as $i => $dci_variable) {
        $dci_default = isset($dci_defaults[$dci_variable]) ? $dci_defaults[$dci_variable] : NULL;
        $value = ConfigResolver::getConfig($dci_variable, $dci_default);
      }
    }
  }

}
