<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\PluginBase
 */

namespace DrupalCI\Plugin;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;

/**
 * Base class for plugins.
 */
abstract class PluginBase {

  // TODO: Perhaps this isnt BuildTaskTrait, but a PluginTrait that figures out
  // configuration?
  use BuildTaskTrait;
  /**
   * The plugin_id.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The plugin implementation definition.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration_overrides
   *   A configuration array containing overrides from the build.yml file.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration_overrides = [], $plugin_id = '', $plugin_definition = []) {
    $this->configuration = $this->getDefaultConfiguration();
    $this->configuration_overrides = $configuration_overrides;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    // Compute the plugin's configuration.
    $this->configure();
    $this->override_config();
  }

  protected function exec($command, &$output, &$return_var) {
    exec($command, $output, $return_var);
  }

}
