<?php

/**
 * @file
 * Contains \DrupalCI\Build\Results\BuildResults.
 */

namespace DrupalCI\Build\Results;

use DrupalCI\Console\Output;
use DrupalCI\Build\BuildInterface;

class BuildResults {

  protected $current_stage;
  public function getCurrentStage() {  return $this->current_stage;  }
  public function setCurrentStage($stage) {  $this->current_stage = $stage;  }

  protected $stage_results;
  public function getStageResults() {  return $this->stage_results;  }
  public function setStageResults(array $stage_results) {  $this->stage_results = $stage_results;  }
  public function getResultByStage($stage) {  return $this->stage_results[$stage];  }
  public function setResultByStage($stage, $result) {  $this->stage_results[$stage] = $result;  }

  protected $current_step;
  public function getCurrentStep() {  return $this->current_step;  }
  public function setCurrentStep($step) {  $this->current_step = $step;  }

  protected $step_results;
  public function getStepResults() {  return $this->step_results;  }
  public function setStepResults(array $step_results) {  $this->step_results = $step_results;  }
  public function getResultByStep($stage, $step) {  return $this->step_results[$stage][$step];  }
  public function setResultByStep($stage, $step, $result)  {  $this->step_results[$stage][$step] = $result;  }

  protected $artifacts;
  public function setArtifacts($artifacts) { $this->artifacts = $artifacts; }
  public function getArtifacts() { return $this->artifacts; }

  protected $publishers = [];
  public function getPublishers() {  return $this->publishers;  }
  public function setPublishers($publishers) {  $this->publishers = $publishers;  }
  public function getPublisher($publisher) {  return $this->publishers[$publisher];  }


  public function __construct(BuildInterface $build) {
    // Set up our initial $step_result values
    $this->initStepResults($build);
  }

  protected function initStepResults(BuildInterface $build) {
    // Retrieve the build step tree from the build definition
    $build_steps = $build->getBuildDefinition()->getBuildSteps();
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
    // @todo: figure out how to show timing without a global.
    global $stepstart;
    if ($status == 'Executing'){
        $stepstart[$build_stage][$build_step] = microtime(TRUE);
      // Output::writeln("<comment><options=bold>Current: $foo</options=bold> </comment>");
    }

    $this->setCurrentStep($build_step);
    $this->setResultByStep($build_stage, $build_step, $status);

    if ($status == 'Completed'){
      $elasped = microtime(TRUE) - $stepstart[$build_stage][$build_step];
      // Output::writeln("<comment>Elapsed: $elasped <options=bold>$status</options=bold> $build_stage:$build_step </comment>");
    }
    Output::writeln("<comment><options=bold>$status</options=bold> $build_stage:$build_step</comment>");
  }

}
