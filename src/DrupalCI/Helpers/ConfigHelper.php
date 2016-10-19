<?php

/**
 * @file
 * DrupalCI Config helper class.
 */

namespace DrupalCI\Helpers;

use DrupalCI\Helpers\DrupalCIHelperBase;
use DrupalCI\Console\Output;

class ConfigHelper extends DrupalCIHelperBase {

  /**
   * {@inheritdoc}
   */
  public function getCurrentEnvVars() {
    $current = [];
    if (!empty($_ENV)) {
      foreach ($_ENV as $key => $value) {
        if (preg_match('/^DCI_/', $key)) {
          $current[$key] = $value;
        }
      }
    }
    return $current;
  }
}
