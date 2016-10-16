<?php

namespace DrupalCI\Build;

/**
 * A service to keep DCI_ variables so we don't have to recompute them.
 */
interface BuildVariablesInterface {

  /**
   * Set a variable to a value.
   *
   * @param string $name
   *   The name of the variable.
   * @param mixed $value
   *   The value to set.
   * @param string $priority
   *   (optional) Optionally set a priority to the value being set. One of:
   *   'default', 'local', 'environment'. Defaults are overridden by locals
   *   which are overridden by environment. Preprocessed variables are
   *   equivalent to environment.
   */
  public function set($name, $value, $priority = 'environment');

  /**
   * Get a variable.
   *
   * @param string $name
   *   The name of the variable.
   * @param mixed $default
   *   (optional) The default value to get, if none is present.
   * @return mixed
   *   The value.
   */
  public function get($name, $default = NULL);

  /**
   * Add a number of variables at a given priority level.
   *
   * @param type $variables
   * @param type $priority
   */
  public function add($variables, $priority = 'environment');

  /**
   * Set all variables.
   *
   * Will overwrite all existing variables.
   *
   * @param array $variables
   *   A whole set of variables, their names as keys.
   * @param string $priority
   *   (optional) Priority for all the variables set.
   */
  public function setAll($variables, $priority = 'environment');

  /**
   * Get all the variables.
   *
   * @return array
   *   All variables.
   */
  public function getAll();
  
}
