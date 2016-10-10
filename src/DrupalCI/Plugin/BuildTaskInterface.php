<?php

namespace Plugin;


/**
 * Interface BuildTaskInterface
 *
 * @package Plugin
 */
interface BuildTaskInterface {

  public function run();
  public function getConfiguration();
  public function getResultCode();
  public function getResultString();
  public function getResult();
  public function getArtifacts();
  public function getConfigurableVariables();
  public function getElapsedTime();

}
