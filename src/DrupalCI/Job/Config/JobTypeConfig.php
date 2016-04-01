<?php

/**
 * @file
 * Contains \DrupalCI\Job\Definition\JobTypeConfig.
 */

namespace DrupalCI\Job\Config;

use Symfony\Component\Yaml\Exception\ParseException;

class JobTypeConfig {

  /**
   * @var string $config_file
   *
   * Location of our source job type configuration file
   */
  protected $config_file;

  /**
   * @return string
   */
  protected function getConfigFile() {
    return $this->config_file;
  }

  /**
   * @param string $config_file
   */
  protected function setConfigFile($config_file) {
    $this->config_file = $config_file;
  }

  /**
   * @var string $template_file
   *
   * Location of our job type default template file
   */
  protected $template_file;

  /**
   * @return string
   */
  protected function getTemplateFile() {
    return $this->template_file;
  }

  /**
   * @param string $template_file
   */
  protected function setTemplateFile($template_file) {
    $this->template_file = $template_file;
  }
  
  /**
   * @var string $job_type
   */
  protected $job_type;

  /**
   * @return string
   */
  public function getJobType()
  {
    return $this->job_type;
  }

  /**
   * @param string $job_type
   */
  public function setJobType($job_type)
  {
    $this->job_type = $job_type;
  }

  /**
   * @var array $available_arguments
   */
  protected $available_arguments;

  /**
   * @return array
   */
  public function getAvailableArguments()
  {
    return $this->available_arguments;
  }

  /**
   * @param array $available_arguments
   */
  public function setAvailableArguments($available_arguments)
  {
    $this->available_arguments = $available_arguments;
  }

  /**
   * @var array $default_arguments
   */
  protected $default_arguments;

  /**
   * @return array
   */
  public function getDefaultArguments()
  {
    return $this->default_arguments;
  }

  /**
   * @param array $default_arguments
   */
  public function setDefaultArguments($default_arguments)
  {
    $this->default_arguments = $default_arguments;
  }

  /**
   * @var array $build_artifacts
   */
  protected $build_artifacts;

  /**
   * @var array $priority_arguments
   */
  protected $priority_arguments;

  /**
   * @return array
   */
  public function getPriorityArguments()
  {
    return $this->priority_arguments;
  }

  /**
   * @param array $priority_arguments
   */
  public function setPriorityArguments($priority_arguments)
  {
    $this->priority_arguments = $priority_arguments;
  }


  /**
   * @return array
   */
  public function getBuildArtifacts()
  {
    return $this->build_artifacts;
  }

  /**
   * @param array $build_artifacts
   */
  public function setBuildArtifacts($build_artifacts)
  {
    $this->build_artifacts = $build_artifacts;
  }


  /**
   * Load the configuration file contents into this object
   *
   * @param string $config_file
   */
  public function __construct($config_file) {
    if ($content = file_get_contents($config_file)) {
      $this->parseConfig($content);
    }
    else {
      throw new ParseException("Unable to parse job type configuration file: $config_file");
    }
  }

  /**
   * Parses the configuration information into this object
   * @param array $content
   */
  protected function parseConfig($content) {
    $this->setJobType($content['jobtype'] ?: 'generic');
    $this->setTemplateFile($content['template'] ?: './drupalci.yml');
    $arguments = $content['arguments'] ?: [];
    $available = [];
    $defaults = [];
    $priority = [];
    foreach ($arguments as $name => $argument) {
      // Available Arguments
      $available["DCI_$name"] = $argument;
      // Default Job Type Arguments
      if (!empty($argument['default_value'] && $argument['include_default'])) {
        $defaults["DCI_$name"] = $argument['default_value'];
      }
      // Priority Arguments
      if (!empty($argument['priority'])) {
        $priority[$argument['priority']] = "DCI_$name";
      }
    }
    ksort($priority);
    $this->setAvailableArguments($available);
    $this->setDefaultArguments($defaults);
    $this->setPriorityArguments(array_values($priority));
    $this->setBuildArtifacts($content['build_artifacts'] ?: []);
  }
  
}