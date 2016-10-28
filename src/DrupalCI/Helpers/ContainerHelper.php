<?php

/**
 * @file
 * DrupalCI Container helper class.
 */

namespace DrupalCI\Helpers;

use DrupalCI\Helpers\DrupalCIHelperBase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ContainerHelper extends DrupalCIHelperBase {

  /**
   * {@inheritdoc}
   */
  public function getContainers($type){
    // TODO: Make sure we're starting from the DrupalCI root
    $option = array();
    // ENVIRONMENT - drupalci docker containers directory
    $containers = glob('containers/'.$type.'/*', GLOB_ONLYDIR);
    foreach ($containers as $container) {
      $option['drupalci/' . explode('/', $container)[2]] = $container;
    }
    return $option;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllContainers() {
    $options = $this->getDbContainers() + $this->getWebContainers() + $this->getPhpContainers() + $this->getBaseContainers();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getDbContainers() {
    return $this->getContainers('database');
  }

  /**
   * {@inheritdoc}
   */
  public function getWebContainers() {
    return $this->getContainers('web');
  }

  /**
   * {@inheritdoc}
   */
  public function getPhpContainers() {
    return $this->getContainers('php');
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseContainers() {
    return $this->getContainers('base');
  }

}
