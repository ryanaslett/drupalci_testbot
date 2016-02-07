<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildStepBase
 */

namespace DrupalCI\Plugin\BuildSteps;

use DrupalCI\Plugin\PluginBase;

/**
 * Base class for Build Step plugins.
 */
abstract class BuildStepBase extends PluginBase {

  /**
   * The build step execution state (Waiting/Initalizing/Executing/Completed/Error)
   *
   * @var string
   */
  protected $state = "Waiting";

  protected function setState($state) {
    $this->state = $state;
  }

  public function getState() {
    return $this->state;
  }

  /**
   * The build step execution result (Pending/Pass/Fail/XFail/XPass/Skipped/Warning/Error/SystemError)
   *
   * @var string
   */
  protected $result = "Pending";

  protected function setResult($result) {
    $this->result = $result;
  }

  public function getResult() {
    return $this->result;
  }

  /**
   * The build step execution summary (Human readable string summarizing results)
   */
  protected $summary;

  protected function setSummary($summary) {
    $this->summary = $summary;
  }

  public function getSummary() {
    return $this->summary;
  }

  protected function update($state, $result, $summary = "") {
    $this->setState($state);
    $this->setResult($result);
    if (!empty($summary)) {
      $this->setSummary($summary);
    }
  }
}
