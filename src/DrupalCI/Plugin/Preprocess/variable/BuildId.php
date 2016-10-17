<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Preprocess\variable\BuildId.
 */

namespace DrupalCI\Plugin\Preprocess\variable;

/**
 * @PluginID("buildid")
 */
class BuildId extends DBUrlBase {

  /**
   * {@inheritdoc}
   */
  //DBX Set 1
  public function process($db_url, $source_value) {
    $db_name = str_replace('-', '_', $source_value);
    $db_name = preg_replace('/[^0-9_A-Za-z]/', '', $db_name);
    return $this->changeUrlPart($db_url, 'path', "/$db_name");
  }
}
