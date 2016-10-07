<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\environment\EnvironmentBase
 */

namespace DrupalCI\Plugin\BuildSteps\environment;

use DrupalCI\Console\Output;
use DrupalCI\Plugin\JobTypes\JobInterface;
use DrupalCI\Plugin\PluginBase;
use Http\Client\Common\Exception\ClientErrorException;

/**
 * Base class for 'environment' plugins.
 */
abstract class EnvironmentBase extends PluginBase {

  public function validateImageNames($containers, JobInterface $job) {
    // Verify that the appropriate container images exist
    Output::writeLn("<comment>Validating container images exist</comment>");
    $docker = $job->getDocker();
    $manager = $docker->getImageManager();
    foreach ($containers as $key => $image_name) {
      $container_string = explode(':', $image_name['image']);
      $name = $container_string[0];

      try {
        $image = $manager->find($name);
      }
      catch (ClientErrorException $e) {
        Output::error("Missing Image", "Required container image <options=bold>'$name:$tag'</options=bold> not found.");
        $job->error();
        return FALSE;
      }
      $id = substr($image->getID(), 0, 8);
      Output::writeLn("<comment>Found image <options=bold>$name/options=bold> with ID <options=bold>$id</options=bold></comment>");
    }
    return TRUE;
  }
}
