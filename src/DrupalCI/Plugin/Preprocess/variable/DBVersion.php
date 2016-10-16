<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Preprocess\variable\DBVersion.
 */

namespace DrupalCI\Plugin\Preprocess\variable;

/**
 * @PluginID("dbversion")
 */
class DBVersion extends DBUrlBase {

  /**
   * {@inheritdoc}
   */
  public function process($db_url, $source_value) {
    $db_type = $this->buildVars->get('DCI_DBType');
    if (!empty($db_type)) {
      // $source_value will be DCI_DBVersion, which looks like: 5.5.
      $host_part = $db_type . '-' . str_replace([':', '.'], '-', $source_value);
      $host = 'drupaltestbot-db-' . $host_part;
      $db_url = $this->changeUrlPart($db_url, 'scheme', $db_type);
      $db_url = $this->changeUrlPart($db_url, 'host', $host);
    }
    return $db_url;
  }

}
