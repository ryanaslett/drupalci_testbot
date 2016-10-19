<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\environment\EnvironmentBase
 */

namespace DrupalCI\Plugin\BuildSteps\environment;

use DrupalCI\Console\Output;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\PluginBase;
use Http\Client\Common\Exception\ClientErrorException;

/**
 * Base class for 'environment' plugins.
 */
abstract class EnvironmentBase extends PluginBase {

  public function validateImageNames($containers, BuildInterface $build) {
    // Verify that the appropriate container images exist
    // OPUT
    Output::writeLn("<comment>Validating container images exist</comment>");
    // DOCKER
    $docker = $build->getDocker();
    $manager = $docker->getImageManager();
    foreach ($containers as $key => $image_name) {
      $container_string = explode(':', $image_name['image']);
      $name = $container_string[0];

      try {
        $image = $manager->find($name);
      }
      catch (ClientErrorException $e) {
        // OPUT
        Output::error("Missing Image", "Required container image <options=bold>'$name'</options=bold> not found.");
        $build->error();
        return FALSE;
      }
      $id = substr($image->getID(), 0, 8);
      // OPUT
      Output::writeLn("<comment>Found image <options=bold>$name/options=bold> with ID <options=bold>$id</options=bold></comment>");
    }
    return TRUE;
  }
}
