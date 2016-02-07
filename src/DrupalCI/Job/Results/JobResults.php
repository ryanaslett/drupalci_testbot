<?php

/**
 * @file
 * Contains \DrupalCI\Job\Results\JobResults.
 */

namespace DrupalCI\Job\Results;

use DrupalCI\Console\Output;
use DrupalCI\Plugin\JobTypes\JobInterface;

class JobResults {

  // Array of build_steps for this job
  protected $build_steps;

  // Initalizing/Executing/Error/Complete status (i.e. STATE) by job
  protected $state;

  // Pass/Fail/XFail/XPass/Error/SystemError result (i.e. OUTCOME) by job
  protected $result;

  // Human readable summary string describing the job results.
  protected $summary;

  /**
   * Build stages and steps
   * $stages[$stage]['state'] => Waiting/Initalizing/Executing/Completed/Error status (i.e. STATE) by build stage
   * $stages[$stage]['result'] => Pending/Pass/Fail/XFail/XPass/Error/SystemError result (i.e. OUTCOME) by build stage
   * $stages[$stage]['summary'] => Human readable summary string describing the build stage results.
   * $stages[$stage]['steps'][$step]['state'] => Waiting/Initalizing/Executing/Completed/Error status (i.e. STATE) by build step
   * $stages[$stage]['steps'][$step]['result'] => Pending/Pass/Fail/XFail/XPass/Error/SystemError result (i.e. OUTCOME) by build step
   * $stages[$stage]['steps'][$step]['summary'] => Human readable summary string describing the build step results.
   * $stages[$stage]['steps'][$step]['output'] => Console output by build step
   */
  protected $stages;

  // Stores a list of build artifacts to be considered part of the final results for this job
  protected $artifacts;


  public function __construct(JobInterface $job) {
    // Inject the Build Steps array from this job
    $this->build_steps = $job->getJobDefinition()->getBuildSteps();
    // Set up our initial $results values
    $this->initStageResults();
  }

  protected function initStageResults() {
    // Retrieve the build step tree from the job definition
    $build_steps = $this->build_steps;
    // Set our initial $job_status
    $this->state = "Initializing";
    $this->result = "No Result";
    $this->summary = "No Run";
    // Set up our initial $stages array
    $stage_results = [];
    foreach ($build_steps as $stage => $steps) {
      $stage_results[$stage] = ['state' => "Waiting", 'result' => "Pending", 'summary' => "Build stage not yet executed."];
      foreach ($steps as $step => $value) {
        $stage_results[$stage]['steps'][$step] = ['state' => "Waiting", 'result' => "Pending", 'summary' => "Build step not yet executed.", 'output' => NULL];
      }
    }
    $this->setStageResults($stage_results);
  }

  // Initalizing/Executing/Error/Complete status (i.e. STATE) by job
  public function getJobState() {
    return $this->state;
  }

  public function setJobState($job_state) {
    $this->state = $job_state;
  }

  // Pass/Fail/XFail/XPass/Error/SystemError result (i.e. OUTCOME) by job
  public function getJobResult() {
    return $this->result;
  }

  public function setJobResult($job_result) {
    $this->result = $job_result;
  }

  // Human readable Job Outcome Summary message
  public function getJobSummary() {
    return $this->summary;
  }

  public function setJobSummary($job_summary) {
    $this->summary = $job_summary;
  }

  // Obtain only the stage results
  public function getStageResults() {
    return $this->stages;
  }

  public function setStageResults(array $stage_results) {
    $this->stages = $stage_results;
  }

  // Initializing/Executing/Error/Complete status (i.e. STATE) by build process stage
  public function getStateByStage($stage) {
    return $this->stages[$stage]['state'];
  }

  public function setStateByStage($stage, $state) {
    $this->stages[$stage]['state'] = $state;
  }

  // Pass/Fail/XFail/XPass/Error/SystemError result (i.e. OUTCOME) by build process stage
  public function getResultByStage($stage) {
    return $this->stages[$stage]['result'];
  }

  public function setResultByStage($stage, $result) {
    $this->stages[$stage]['result'] = $result;
  }

  // Human readable result summary message by build process stage
  public function getSummaryByStage($stage) {
    return $this->stages[$stage]['summary'];
  }

  public function setSummaryByStage($stage, $summary) {
    $this->stages[$stage]['summary'] = $summary;
  }

  // Obtain only the step results for a given step
  public function getStepResults($stage, $step) {
    return $this->stages[$stage]['steps'][$step];
  }

  public function setStepResults($stage, $step, $step_results) {
    $this->stages[$stage]['steps'][$step] = $step_results;
  }

  // Initializing/Executing/Error/Complete status (i.e. STATE) by build process step
  public function getStateByStep($stage, $step) {
    return $this->stages[$stage]['steps'][$step]['state'];
  }

  public function setStateByStep($stage, $step, $state) {
    $this->stages[$stage]['steps'][$step]['state'] = $state;
  }

  // Pass/Fail/XFail/XPass/Error/SystemError result (i.e. OUTCOME) by build process step
  public function getResultByStep($stage, $step) {
    return $this->stages[$stage]['steps'][$step]['result'];
  }

  public function setResultByStep($stage, $step, $result) {
    $this->stages[$stage]['steps'][$step]['result'] = $result;
  }

  // Human readable result summary message by build process stage
  public function getSummaryByStep($stage, $step) {
    return $this->stages[$stage]['steps'][$step]['summary'];
  }

  public function setSummaryByStep($stage, $step, $summary) {
    $this->stages[$stage]['steps'][$step]['summary'] = $summary;
  }

  // Console output for each build process step within the job
  public function getOutputByStep($stage, $step) {
    return $this->stages[$stage]['steps'][$step]['output'];
  }

  public function setOutputByStep($stage, $step, $output) {
    $this->stages[$stage]['steps'][$step]['output'] = $output;
  }

  // Tracks which stage of the build process is currently being executed
  protected $current_stage;

  public function getCurrentStage() {
    return $this->current_stage;
  }

  public function setCurrentStage($stage) {
    $this->current_stage = $stage;
  }

  // Tracks which step of the current build process stage is currently being executed
  protected $current_step;

  public function getCurrentStep() {
    return $this->current_step;
  }

  public function setCurrentStep($step) {
    $this->current_step = $step;
  }


  // List of build artifacts to be considered part of the final results for this job
  public function setArtifacts($artifacts) {
    $this->artifacts = $artifacts;
  }

  public function getArtifacts() {
    return $this->artifacts;
  }


  // Convenience functions for updating build progress and results
  public function updateStageStatus($build_stage, $status, $result = "") {
    $this->setCurrentStage($build_stage);
    $this->setStateByStage($build_stage, $status);
    if (!empty($result)) {
      $this->setResultByStage($build_stage, $result);
    }
    // TODO: Determine if we have any publishers, and provide in-progress updates if we do.
    Output::writeln("<comment><options=bold>$status</options=bold> $build_stage $result</comment>");
  }

  public function updateStepStatus($build_stage, $build_step, $status, $result = "", $summary = "", $output = NULL) {
    $this->setCurrentStep($build_step);
    $this->setStateByStep($build_stage, $build_step, $status);
    if (!empty($result)) {
      $this->setResultByStep($build_stage, $build_step, $result);
    }
    if (!empty($summary)) {
      $this->setSummaryByStep($build_stage, $build_step, $summary);
    }
    if (!empty($output)) {
      $this->setOutputByStep($build_stage, $build_step, $output);
    }
    Output::writeln("<comment><options=bold>$status</options=bold> $build_stage:$build_step $result</comment>");
  }


  // TODO: Consider adding a 'job publisher' class for interim feedback and/or real-time display
  /*
  // Pasting this code here for future reference, once we revisit interacting with a results API.

  public function prepServerForPublishing(JobDefinition $jobDefinition) {
    // If we are publishing this job to a results server (or multiple), prep the server
      $definition = $jobDefinition->getDefinition();
      if (!empty($definition['publish']['drupalci_results'])) {
      $results_data = $job_definition['publish']['drupalci_results'];
      // $data format:
      // i) array('config' => '<configuration filename>'),
      // ii) array('host' => '...', 'username' => '...', 'password' => '...')
      // or a mixed array of the above
      // iii) array(array(...), array(...))
      // Normalize data to the third format, if necessary
      $results_data = (count($results_data) == count($results_data, COUNT_RECURSIVE)) ? [$results_data] : $results_data;
    }
    else {
      $results_data = array();
    }

  public function publishProgressToServer() {
    // If we are publishing this job to a results server (or multiple), update the progress on the server(s)
    // TODO: Check current state, and don't progress if already there.
    foreach ($results_data as $key => $instance) {
      $job->configureResultsAPI($instance);
      $api = $job->getResultsAPI();
      $url = $api->getUrl();
      // Retrieve the results node ID for the results server
      $host = parse_url($url, PHP_URL_HOST);
      $states = $api->states();
      $results_id = $job->getResultsServerID();

      foreach ($states as $subkey => $state) {
        if ($build_step == $subkey) {
          $api->progress($results_id[$host], $state['id']);
          break;
        }
      }
    }
  }
  */

  /*
  // Stores a list of publishers which should be notified of the results upon job completion
  protected $publishers = [];

  public function getPublishers() {
    return $this->publishers;
  }

  public function setPublishers($publishers) {
    $this->publishers = $publishers;
  }

  public function getPublisher($publisher) {
    return $this->publishers[$publisher];
  }
*/

}