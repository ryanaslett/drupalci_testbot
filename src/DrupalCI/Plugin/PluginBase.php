<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\PluginBase
 */

namespace DrupalCI\Plugin;

use DrupalCI\Injectable;
use DrupalCI\InjectableTrait;
use DrupalCI\Plugin\JobTypes\JobInterface;
use Pimple\Container;

/**
 * Base class for plugins.
 */
abstract class PluginBase implements Injectable {

  /**
   * The console output.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  /**
   * {@inheritdoc}
   */
  public function setContainer(Container $container) {
    $this->output = $container['console.output'];
  }

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
   * Configuration information passed into the plugin.
   *
   * When using an interface like
   * \Drupal\Component\Plugin\ConfigurablePluginInterface, this is where the
   * configuration should be stored.
   *
   * Plugin configuration is optional, so plugin implementations must provide
   * their own setters and getters.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration = [], $plugin_id = '', $plugin_definition = []) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

}
