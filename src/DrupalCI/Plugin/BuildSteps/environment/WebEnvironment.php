<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\environment\WebEnvironment
 *
 * Processes "environment: web:" parameters from within a build definition,
 * ensures appropriate Docker container images exist, and defines the
 * appropriate execution container for communication back to BuildBase.
 */

namespace DrupalCI\Plugin\BuildSteps\environment;

use DrupalCI\Console\Output;
use DrupalCI\Build\BuildInterface;

/**
 * @PluginID("web")
 */
class WebEnvironment extends EnvironmentBase {

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, $data) {
    // Data format: '5.5' or array('5.4', '5.5')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $data = is_array($data) ? $data : [$data];
    Output::writeLn("<info>Parsing required Web container image names ...</info>");
    $containers = $build->getExecContainers();
    $containers['web'] = $this->buildImageNames($data, $build);
    $valid = $this->validateImageNames($containers['web'], $build);
    if (!empty($valid)) {
      $build->setExecContainers($containers);
      // Actual creation and configuration of the executable containers occurs
      // during the 'getExecContainers()' method call.
    }
  }

  protected function buildImageNames($data, BuildInterface $build) {
    $images = [];
    foreach ($data as $key => $php_version) {
      $images["web-$php_version"]['image'] = "drupalci/web-$php_version";
      Output::writeLn("<comment>Adding image: <options=bold>drupalci/web-$php_version</options=bold></comment>");
    }
    return $images;
  }

}
