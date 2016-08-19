<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Preprocess\definition\UseLocalCodebase
 *
 * Preprocesses the DCI_UseLocalCodebase variable, overriding the job's
 * setup:checkout: section for use with a local code base.
 */

namespace DrupalCI\Plugin\Preprocess\definition;

/**
 * @PluginID("uselocalcodebase")
 */
class UseLocalCodebase {

  /**
   * {@inheritdoc}
   *
   * DCI_UseLocalCodebase_Preprocessor
   *
   * Takes a string defining a local directory to use as the codebase for
   * testing, and overrides the 'setup:checkout:' array within the given
   * job definition to use the local codebase.
   */
  public function process(array &$definition, $source_directory, $dci_variables) {
    // Override all job checkout steps
    $definition['setup']['checkout'] = [];
    $definition['setup']['checkout']['protocol'] = 'local';
    $definition['setup']['checkout']['source_dir'] = $source_directory;
  }

}
