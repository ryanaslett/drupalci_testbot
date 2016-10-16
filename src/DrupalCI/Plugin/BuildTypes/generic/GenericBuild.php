<?php

/**
 * @file
 * Build class for 'Generic' builds on DrupalCI.
 *
 * A generic build simply runs through and executes the build definition steps as
 * defined within the passed build definition file.
 */

namespace DrupalCI\Plugin\BuildTypes\generic;

use DrupalCI\Build\BuildBase;

/**
 * @PluginID("generic")
 */

class GenericBuild extends BuildBase {

  /**
   * @var string
   */
  public $buildType = 'generic';

  /**
   * Overrides the getDefaultDefinitionTemplate() method from within BuildBase.
   *
   * For 'generic' build types, if no file is provided, we assume the presence of
   * a drupalci.yml file in the current working directory.
   *
   * @param $build_type
   *   The name of the build type, used to select the appropriate subdirectory
   *
   * @return string
   *   The location of the default build definition template
   */
  public function getDefaultDefinitionTemplate($build_type) {
    return "./drupalci.yml";
  }

  public function getBuildArtifacts() {
    return [];
  }

}
