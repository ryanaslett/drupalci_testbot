<?php

/**
 * @file
 * Base Helper class for Drupal CI.
 */

namespace DrupalCI\Console\Helpers;


/**
 * Just some helpful debugging stuff for now.
 */
class DrupalCIHelperBase {

  public function locate_binary($cmd) {
    return shell_exec("command -v " . escapeshellcmd($cmd));
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->errors;
  }

}
