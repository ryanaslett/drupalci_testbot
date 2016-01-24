<?php

/**
 * @file
 * Contains \DrupalCI\Job\Results\JobResults.
 */

namespace DrupalCI\Job\Results;

use DrupalCI\Console\Output;
use DrupalCI\Plugin\JobTypes\JobInterface;

class JobResults {

  // Tracks which stage of the build process is currently being executed
  protected $current_stage;

  public function getCurrentStage() {
    return $this->current_stage;
  }

  public function setCurrentStage($stage) {
    $this->current_stage = $stage;
  }

  // Tracks the pass/fail/skipped/error status by job
  protected $job_results;

  public function getJobResults()
  {
    return $this->job_results;
  }

  public function setJobResults($job_results)
  {
    $this->job_results = $job_results;
  }

  // Tracks the pass/fail/skipped/error status by build process stage
  protected $stage_results;

  public function getStageResults() {
    return $this->stage_results;
  }

  public function setStageResults(array $stage_results) {
    $this->stage_results = $stage_results;
  }

  public function getResultByStage($stage) {
    return $this->stage_results[$stage];
  }

  public function setResultByStage($stage, $result) {
    $this->stage_results[$stage] = $result;
  }

  // Tracks which step of the current build process stage is currently being executed
  protected $current_step;

  public function getCurrentStep() {
    return $this->current_step;
  }

  public function setCurrentStep($step) {
    $this->current_step = $step;
  }

  // Tracks the pass/fail/skipped/error status for each build process step within the job
  protected $step_results;

  public function getStepResults() {
    return $this->step_results;
  }

  public function setStepResults(array $step_results) {
    $this->step_results = $step_results;
  }

  public function getResultByStep($stage, $step) {
    return $this->step_results[$stage][$step];
  }

  public function setResultByStep($stage, $step, $result) {
    $this->step_results[$stage][$step] = $result;
  }

  // Stores the console output for each build process step within the job
  protected $step_output;

  public function getStepOutput() {
    return $this->step_output;
  }

  public function setStepOutput(array $step_output) {
    $this->step_output = $step_output;
  }

  public function getOutputByStep($stage, $step) {
    return $this->step_output[$stage][$step];
  }

  public function setOutputByStep($stage, $step, $output) {
    $this->step_output[$stage][$step] = $output;
  }

  // Stores a list of job artifacts to be considered part of the final results for this job
  protected $artifacts;

  public function setArtifacts($artifacts) {
    $this->artifacts = $artifacts;
  }

  public function getArtifacts() {
    return $this->artifacts;
  }

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


  public function __construct(JobInterface $job) {
    // Set up our initial $step_result values
    $this->initStepResults($job);
  }

  protected function initStepResults(JobInterface $job) {
    // Retrieve the build step tree from the job definition
    $build_steps = $job->getJobDefinition()->getBuildSteps();
    // Set up our initial $step_result values
    $step_results = [];
    foreach ($build_steps as $stage => $steps) {
      foreach ($steps as $step => $value) {
        $step_results[$stage][$step] = ['run status' => 'No run'];
      }
    }
    $this->setStepResults($step_results);
  }

  public function updateStageStatus($build_stage, $status) {
    $this->setCurrentStage($build_stage);
    $this->setResultByStage($build_stage, $status);
    // TODO: Determine if we have any publishers, and progress the build step if we do.
    Output::writeln("<comment><options=bold>$status</options=bold> $build_stage</comment>");
  }

  public function updateStepStatus($build_stage, $build_step, $status) {
    $this->setCurrentStep($build_step);
    $this->setResultByStep($build_stage, $build_step, $status);
    Output::writeln("<comment><options=bold>$status</options=bold> $build_stage:$build_step</comment>");
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

}