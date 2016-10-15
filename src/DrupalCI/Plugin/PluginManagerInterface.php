<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\PluginManagerInterface.
 */

namespace DrupalCI\Plugin;

use DrupalCI\Plugin\BuildTaskInterface;

interface PluginManagerInterface {

  /**
   * @param $type
   * @param $plugin_id
   * @param array $configuration
   * @return \DrupalCI\Plugin\BuildTaskInterface
   */
  public function getPlugin($type, $plugin_id, $configuration = []);

}
