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
use DrupalCI\Injectable;
use Pimple\Container;

/**
 * Base class for 'environment' plugins.
 */
abstract class EnvironmentBase extends PluginBase implements Injectable {

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  public function inject(Container $container) {
    $this->io = $container['console.io'];
  }

  public function validateImageNames($containers, BuildInterface $build) {
    // Verify that the appropriate container images exist
    // OPUT
    $this->io->writeLn("<comment>Validating container images exist</comment>");
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
        $this->io->drupalCIError("Missing Image", "Required container image <options=bold>'$name'</options=bold> not found.");
        $build->error();
        return FALSE;
      }
      $id = substr($image->getID(), 0, 8);
      // OPUT
      $this->io->writeLn("<comment>Found image <options=bold>$name/options=bold> with ID <options=bold>$id</options=bold></comment>");
    }
    return TRUE;
  }
}
