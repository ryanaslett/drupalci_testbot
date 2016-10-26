<?php

namespace DrupalCI\Plugin\BuildSteps\generic;

use DrupalCI\Console\Output;

/**
 * Processes "[build_step]: testcommand:" instructions from within a Build definition.
 *
 * @PluginID("testcommand")
 */
class ContainerTestingCommand extends ContainerCommand {

  /*
   * Overrides ContainerCommands check with a specific signal check.
   */
  protected function checkCommandStatus($signal) {
    if ($signal > 1) {
      Output::error('Error', "Received a failed return code from the last command executed on the container.  (Return status: " . $signal . ")");
      return 1;
    }
    else {
      return 0;
    }
  }
}