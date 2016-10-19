<?php

/**
 * @file
 * DrupalCI Config helper class.
 */

namespace DrupalCI\Helpers;

use DrupalCI\Helpers\DrupalCIHelperBase;
use DrupalCI\Console\Output;

class ConfigHelper extends DrupalCIHelperBase {

  /**
   * {@inheritdoc}
   */
  public function getAllConfigSets(){
    // TODO: Filter out 'directories'
    $homedir = getenv('HOME');
    $configsets = array();
    // ENVIRONMENT - user configset directory
    $options = glob($homedir . '/.drupalci/configs/*');
    foreach ($options as $option) {
      $option_parts = explode('/', $option);
      $filename = array_pop($option_parts);
      $configsets[$filename] = $option;
    }
    return $configsets;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfigSets() {
    $configsets = array();
    // TODO: Fix the hardcoded directory
    // ENVIRONMENT - drupalci configset directory
    $options = glob('./configsets/*');
    foreach ($options as $option) {
      $optionpart = explode('/', $option);
      $filename = array_pop($optionpart);
      $configsets[$filename] = $option;
    }
    return $configsets;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentConfigSetContents() {
    $homedir = getenv('HOME');
    // ENVIRONMENT - user configset directory
    $filename = $homedir . '/.drupalci/config';
    $options = array();
    if (file_exists($filename)) {
      $handle = fopen($filename, "r");
      if ($handle) {
        while (($line = fgets($handle)) !== false) {
          $options[] = str_replace(array("\r", "\n"), "", $line);
        }
      }
      fclose($handle);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentConfigSetParsed() {
    $config = $this->getCurrentConfigSetContents();
    $parsed = array();
    foreach ($config as $line) {
      $value = explode("=", $line);
      if (!empty($value[0]) && !empty($value[1])) {
        $parsed[$value[0]] = $value[1];
      }
    }
    return $parsed;
  }

  /**
   * {@inheritdoc}
   */
  public function activateConfig($configset) {
    $homedir = getenv('HOME');
    $configsets = $this->getAllConfigSets();
    if (in_array($configset, array_keys($configsets))) {
      // ENVIRONMENT - users configset directory
      $destination = $homedir . '/.drupalci/config';
      if (copy($configsets[$configset], $destination)) {
        return true;
      }
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentEnvVars() {
    $current = array();
    if (!empty($_ENV)) {
      foreach ($_ENV as $key => $value)  {
        if (preg_match('/^DCI_/', $key )) {
          $current[$key] = $value;
        }
      }
    }
    else {
      // TODO: Error message regarding ensuring 'E' is set in the server's "variables_order" config setting.
    }
    return $current;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigVariable($key, $value) {
    $config = $this->getCurrentConfigSetParsed();
    $config[$key] = $value;
    $this->writeConfig($config);
  }

  /**
   * {@inheritdoc}
   */
  public function clearConfigVariable($key) {
    $config = $this->getCurrentConfigSetParsed();
    unset($config[$key]);
    $this->writeConfig($config);
  }

  /**
   * {@inheritdoc}
   */
  public function writeConfig($config) {
    $homedir = getenv('HOME');
    // ENVIRONMENT - user config directory
    $configpath = $homedir . '/.drupalci';
    $filename = $configpath . '/config';
    if (!file_exists($configpath)) {
      mkdir($configpath);
    }
    $handle = fopen($filename, "w");
    if ($handle) {
      foreach ($config as $key => $value) {
        fwrite($handle, $key . "=" . $value . "\n");
      }
    }
    fclose($handle);
  }

  /**
   * {@inheritdoc}
   */
  function saveCurrentConfig($config_name) {
    $homedir = getenv('HOME');
    // ENVIRONMENT - user config directory

    $configpath = $homedir . '/.drupalci/';
    $current = $configpath . 'config';
    $filename = $configpath . 'configs/' . $config_name;
    if (file_exists($current)) {
      copy($current, $filename);
    }
  }
}
