<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\setup\Checkout
 *
 * Processes "setup: checkout:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\BuildSteps\execute;

use DrupalCI\Console\Output;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("simpletest")
 */
class Simpletest extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $job, $data) {
    throw new \Exception('simpletest data: ' . print_r($data, true));
  }

}
