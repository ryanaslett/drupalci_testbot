<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\PluginBase
 */

namespace DrupalCI\Plugin;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use Pimple\Container;

/**
 * Base class for plugins.
 */
abstract class PluginBase implements Injectable, BuildTaskInterface {

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
   * Any variables that can affect the behavior of this plugin, that are
   * specific to this plugin, reside in a configuration array within the plugin.
   *
   * @var array
   *
   */
  protected $configuration = [];

  /**
   * Configuration overrides passed into the plugin.
   *
   * @var array
   */
  protected $configuration_overrides;

  /**
   * The plugin implementation definition.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  /**
   * The container.
   *
   * We need this to inject into other objects.
   *
   * @var \Pimple\Container
   */
  protected $container;

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

  public function inject(Container $container) {
    $this->io = $container['console.io'];
    $this->container = $container;
  }

  /**
   * @inheritDoc
   */
  public function getComputedConfiguration() {
    return $this->configuration;
  }

  protected function override_config() {

    if (!empty($this->configuration_overrides)) {
      if ($invalid_overrides = array_diff_key($this->configuration_overrides, $this->configuration)){
        // @TODO: somebody is trying to override a non-existant configuration value. Throw an exception? print a warning?
      }
      $this->configuration = array_merge($this->configuration, array_intersect_key($this->configuration_overrides, $this->configuration));
    }
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getShortError() {
    return 'No short error defined';
  }

  /**
   * @inheritDoc
   */
  public function getErrorDetails() {
    return 'Error Details undefined';
  }

}
