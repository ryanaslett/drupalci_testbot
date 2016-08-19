<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Preprocess\definition\ResultsDirectory
 *
 * PreProcesses DCI_ResultsDirectory variable, and creates the requested
 * directory.  This directory can then be used as the destination for the
 * DCI_XMLOutput variable, which requires a directory that already exists.
 */
namespace DrupalCI\Plugin\Preprocess\definition;

/**
 * @PluginID("resultsdirectory")
 */
class ResultsDirectory {

  /**
   * {@inheritdoc}
   *
   * DCI_ResultsDirectory_Preprocessor
   *
   * Takes a directory string and attempts to create that directory within the
   * container.  The primary use case is to create a results directory that can
   * then be used as the XMLOutput destination.
   */
  public function process(array &$definition, $value, $dci_variables) {
    if (empty($definition['pre-install']['mkdir'])) {
      $definition['pre-install']['mkdir'] = [];
    }
    // TODO: Sanitize to ensure we're not traversing out of the working directory
    $definition['pre-install']['mkdir'][] = $value;
  }

}
