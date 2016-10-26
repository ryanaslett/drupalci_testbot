<?php

namespace DrupalCI\Plugin;

use DrupalCI\Plugin\BuildTaskInterface;

/**
 * Support cascading config resolution in plugins.
 */
trait BuildTaskTrait {

  /**
   * Build variables service.
   *
   * Your class must set this from the container.
   *
   * @var \DrupalCI\Build\BuildVariablesInterface
   */
  protected $buildVars;

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
  protected function resolveDciVariables(&$config) {
    if ($this instanceof BuildTaskInterface) {
      // Use $this->dciReplace() as a callback for walking the array.
      array_walk_recursive($config, [$this, 'dciReplace'], [$this->getDefaultConfiguration(), $this->buildVars]);
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
   * @param array $injection
   *   - Array of the default config for this plugin.
   *   - The build variables service so we can look things up.
   */
  private function dciReplace(&$value, $key, $injection) {
    if (preg_match_all("/%(.*?)%/", $value, $match)) {
      $defaults = $injection[0];
      /* @var $build_vars \DrupalCI\Build\BuildVariablesInterface */
      $build_vars = $injection[1];
      foreach ($match[1] as $i => $dci_name) {
        $dci_default = isset($defaults[$dci_name]) ? $defaults[$dci_name] : '';
        $value = $build_vars->get($dci_name, $dci_default);
      }
    }
  }

}
