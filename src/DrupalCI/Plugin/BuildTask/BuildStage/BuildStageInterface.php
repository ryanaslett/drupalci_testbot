<?php

namespace Plugin\BuildStage;


interface BuildStageInterface {

  public function getJobs();

  public function getDefaultConfig();
}
