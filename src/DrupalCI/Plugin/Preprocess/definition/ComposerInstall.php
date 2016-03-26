<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\Preprocess\definition\ComposerInstall
 */

namespace DrupalCI\Plugin\Preprocess\definition;

/**
 * @PluginID("composerinstall")
 *
 * PreProcesses DCI_ComposerInstall variables, updating the job definition with
 * a setup:composer:install section.  To use set DCI_ComposerInstall=true.
 */

class ComposerInstall {

  /**
   * {@inheritdoc}
   */
  public function process(array &$definition, $value, $dci_variables) {
    // Check to see if Composer Install is set and true
    if (strtolower($value) !== 'true') {
      return;
    }

    if (empty($definition['setup'])) {
      // Insert the setup step at the appropriate spot in the definition.
      // If ['install'] exists, put it immediately before that key.  If not,
      // put it before the ['execute'] key.
      $new_array = [];
      $search_key = (!empty($definition['install'])) ? 'install' : 'execute';
      foreach ($definition as $key => $details) {
        if ($key == $search_key) {
          $new_array['setup'] = [];
        }
        $new_array[$key] = $details;
      }
      $definition = $new_array;
    }

    if (empty($definition['setup']['composer'])) {
      $definition['setup']['composer'] = [];
    }

    // Run additional composer steps for contrib modules.
    if ($this->hasComposerDependencies($definition)) {
      $definition['setup']['composer'][] = 'config repositories.drupal composer https://packagist.drupal-composer.org --working-dir ';
      $definition['setup']['composer'][] = 'require mile23/drupal-merge-plugin --working-dir ';
      $definition['setup']['composer'][] = 'update --working-dir ';
    }
    else {
      $definition['setup']['composer'][] = 'install --prefer-dist --working-dir ';
    }
  }

  /**
   * Check to see if additional repositories make use of composer.
   *
   * This will return TRUE for all cases where an additional repository is
   * defined in the job because at this point the job is being preprocessed,
   * and no files have been downloaded yet.
   *
   * @param array $definition
   *   The job definition array.
   * @return boolean
   */
  protected function hasComposerDependencies(array $definition) {
    return (count($definition['setup']['checkout']) > 1 && is_array($definition['setup']['checkout'][0]));
  }
}
