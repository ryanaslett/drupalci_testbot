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
use DrupalCI\Plugin\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("simpletest")
 */
class Simpletest extends PluginBase implements BuildTaskInterface {

  use BuildTaskTrait;

  public function getDefaultConfiguration() {
    return [
      'DCI_DBurl' => '',
      'DCI_RTColor' => TRUE,
      'DCI_RTConcurrency' => 1,
      'DCI_RTDieOnFail' => TRUE,
      'DCI_RTKeepResultsTable' => FALSE,
      'DCI_RTSqlite' => '',
      'DCI_RTUrl' => 'http://localhost/checkout',
      'DCI_RTVerbose' => TRUE,
      'DCI_RTXmlPath' => '',
      'DCI_RunScript' => '',
      'DCI_TestGroups' => '--all',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $job, $data) {
    $data = $this->resolveDciVariables($data);
    throw new \Exception('simpletest data: ' . print_r($data, true));
  }

}
