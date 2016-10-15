<?php

/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\setup\Composer
 */

namespace DrupalCI\Plugin\BuildSteps\setup;

use DrupalCI\Build\BuildInterface;

/**
 * @PluginID("composer")
 *
 * Processes "setup: composer:" instructions from within a build
 * definition.
 */
class Composer extends SetupBase {

  /**
   * {@inheritdoc}
   *
   * @param string|array $arguments
   *   Arguments for a composer command. May be a string if one composer command
   *   is required to run or an array if multiple commands should run.
   */
  public function run(BuildInterface $build, $data) {
    // Normalize the arguments to an array format.
    $data_list = (array) $data;

    $workingdir = $build->getCodebase()->getWorkingDir();

    foreach ($data_list as $item) {
      $cmd = $this->buildComposerCommand($item, $workingdir);
      $this->exec($cmd, $cmdoutput, $result);
    }
  }

  /**
   * Returns a full composer command based on the passed-in arguments.
   *
   * @param string $arguments
   *   The arguments for the composer command.
   *
   * @return string
   *   The full composer command string.
   */
  protected function buildComposerCommand($data, $workingdir) {
    return "./bin/composer $data $workingdir";
  }

}
