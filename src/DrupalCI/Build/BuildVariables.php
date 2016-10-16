<?php

namespace DrupalCI\Build;

use DrupalCI\Build\BuildVariablesInterface;
use DrupalCI\Plugin\PluginManagerInterface;

/**
 * A service to keep DCI_ variables so we don't have to recompute them.
 *
 * Build is not a verb.
 */
class BuildVariables implements BuildVariablesInterface {
  
  /**
   * Ordering strings.
   *
   * Note that these are in descending order of importance. IE 'preprocessed' is
   * most important.
   *
   * @var string[]
   */
  static protected $ordering = [
    'commandline',
    'preprocess',
    'environment',
    'local',
    'default',
  ];

  /**
   * The variables that have been stored.
   *
   * @var string[]
   */
  protected $variables = [];

  /**
   * Preprocess plugin manager.
   *
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $preprocessPluginManager;

  public function __construct(PluginManagerInterface $preprocess_plugin_manager) {
    $this->preprocessPluginManager = $preprocess_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function set($name, $value, $priority = 'environment', $preprocess = TRUE) {
    if ($preprocess) {
      $this->preprocess($name, $value);
    }
    $this->variables[$priority][$name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function get($name, $default = NULL) {
    foreach (self::$ordering as $priority) {
      if (isset($this->variables[$priority][$name])) {
        return $this->variables[$priority][$name];
      }
    }
    return $default;
  }

  public function add($variables, $priority = 'environment') {
    foreach ($variables as $key => $value) {
      $this->set($key, $value, $priority, FALSE);
    }
  }

  public function setAll($variables, $priority = 'environment') {
    $this->variables = [];
    foreach ($variables as $key => $value) {
      $this->set($key, $value, $priority);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    $variables = [];
    foreach (array_reverse(self::$ordering) as $priority) {
      if (isset($this->variables[$priority])) {
        $variables = array_merge($variables, $this->variables[$priority]);
      }
    }
    return $variables;
  }

  /**
   * Preprocess a build variable by name.
   *
   * Beware circular processing.
   *
   * @param string $dci_name
   *
   * @deprecated We want to get rid of variable preprocessors.
   */
  protected function preprocess($dci_name, $value) {
    if (preg_match('/^DCI_(.+)$/i', $dci_name, $matches)) {
      $name = strtolower($matches[1]);
      if ($this->preprocessPluginManager->hasPlugin('variable', $name)) {
        $existing_variables = array_keys($this->getAll());
        /* @var $plugin \DrupalCI\Plugin\Preprocess\VariableInterface */
        $plugin = $this->preprocessPluginManager->getPlugin('variable', $name);
        $targets = $plugin->target();
        foreach ($targets as $target) {
          if (in_array($target, $existing_variables)) {
            $this->variables['preprocess'][$target] =
              $plugin->process(
                $this->get($target, NULL, FALSE),
                $value
              );
          }
        }
      }
    }
  }

}
