<?php

namespace DrupalCI\Build;

use DrupalCI\Helpers\ConfigHelper;

/**
 * Gleans a config value for a variable name.
 */
class ConfigResolver {

  /**
   * Gleans a config value from various sources, with substitution.
   *
   * A config could look like any of these:
   *   key: value
   *   key: %DCI_substitution%
   *
   * This resolver will:
   *   - Figure out if the value is a substition.
   *   - Assume the substitution is an environmental variable.
   *   -
   *
   * Currently in drupalci, we can specify config in the following places:
   *   - Two default values per task plugin,
   *   - A config override from the local config set.
   *   - An environmental variable.
   *
   * @param string $name
   * @param mixed $platform_default
   * @param mixed $default
   *
   * @return mixed|null
   *   The resolved value for the given config name, or NULL.
   */
  static public function getConfig($name, $platform_default = NULL, $default = NULL) {
    $value = $platform_default;
    if ($default !== NULL) {
      $value = $default;
    }

    // @todo: Figure out how to do this without re-instantiating the helper
    // every time.
    $helper = new ConfigHelper();
    $overrides = $helper->getCurrentConfigSetParsed();
    $overrides = $overrides + $helper->getCurrentEnvVars();

    if (isset($overrides[$name])) {
      $value = $overrides[$name];
    }

    return $value;
  }

}

