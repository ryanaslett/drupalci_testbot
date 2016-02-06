<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\publish\EmailResults
 *
 * Processes "publish: email:" instructions from within a job definition.
 * Gathers the resulting job artifacts and pushes them to an email address.
 */

namespace DrupalCI\Plugin\BuildSteps\postprocess;
use DrupalCI\Plugin\BuildSteps\BuildStepBase;

/**
 * @PluginID("email_results")
 */
class EmailResults extends BuildStepBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'EmailResults plugin not yet implemented.';
  }

}
