<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\publish\GatherArtifacts
 *
 * Processes "publish: gather_artifacts:" instructions from within a build definition.
 * Generates build artifact files in a common directory.
 */

namespace DrupalCI\Plugin\BuildSteps\publish;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Console\Output;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use Pimple\Container;

/**
 * @PluginID("gather_artifacts")
 */
class GatherArtifacts extends PluginBase implements BuildTaskInterface, Injectable {

  use BuildTaskTrait;

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  public function getDefaultConfiguration() {
    return [];
  }

  /**
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildStepPluginManager;

  public function inject(Container $container) {
    $this->io = $container['console.io'];
    $this->buildStepPluginManager = $container['plugin.manager.factory']->create('BuildSteps');
    $this->buildVars = $container['build.vars'];
  }

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, &$config) {
    $config = $this->resolveDciVariables($config);
    // ENVIRONMENT - artifact directory
    $target_directory = $config['artifact_directory'];

    // Since some artifact information could be generated based on DCI_
    // variables, we wait until this late in the process to create the artifact
    // list.
    // @todo: Refactor artifact management into a separate class for sanity.
    $build->createArtifactList();

    // OPUT
    $this->io->writeLn("<comment>Gathering build artifacts in a common directory ...</comment>");

    // Create the destination directory
    // ENVIRONMENT - artifact directory
    if (!empty($target_directory)) {
      $command = $this->buildStepPluginManager->getPlugin('generic', 'mkdir', [$target_directory]);
      $command->run($build, $target_directory);
    }

    // Store the directory in our build object
    $build->setArtifactDirectory($target_directory);

    // Retrieve the list of build artifacts from the build
    $artifacts = $build->getArtifacts();

    // Iterate over the build artifacts
    foreach ($artifacts->getArtifacts() as $key => $artifact) {
      if ($key == 'buildDefinition') {
        $destination_filename = $artifact->getValue();

        // Retrieve the build definition from the build
        // @todo: Plugin-scoped config means the build object's build definition
        // won't have DCI_ variable replacements, so fix that.
        $definition = $build->getBuildDefinition()->getDefinition();
        // write the build definition out to a file in the artifact directory on the container.
        if (!empty($destination_filename)) {
          $file = $target_directory . DIRECTORY_SEPARATOR . $destination_filename;
          // TODO: Verify file name - unique, empty, etc.
          $cmd = "cat >$file <<EOL \n" . print_r($definition, TRUE) . "\nEOL";
          $command = $this->buildStepPluginManager->getPlugin('generic', 'command', [$cmd]);
          $command->run($build, $cmd);
        }
        else {
          // TODO: Exception handling
          // OPUT
          $this->io->writeLn('<info>Error generating build definition build artifact.');
        }
      }
      elseif (strtolower($artifact->getType()) == 'file' || strtolower ($artifact->getType()) == 'directory') {
        // Copy artifact file to the build artifacts directory
        $file = $artifact->getValue();
        $dest = $target_directory . DIRECTORY_SEPARATOR . basename($file);
        if ($file !== $dest) {
          $cmd = "cp -r $file $dest";
          $command = $this->buildStepPluginManager->getPlugin('generic', 'command', [$cmd]);
          $command->run($build, $cmd);
        }
        else {
          // OPUT
          $this->io->writeLn("<info>Skipping $file, as it already exists in the build artifact directory.");
        }
      }
      elseif (strtolower($artifact->getType) == 'string') {
        // Write string to new file with filename based on the string's key
        $dest = $target_directory . DIRECTORY_SEPARATOR . $key;
        $content = $artifact->getValue;
        $cmd = "cat >$dest <<EOL \n" . print_r($content, TRUE) . "\nEOL";
        $command = $this->buildStepPluginManager->getPlugin('generic', 'command', [$cmd]);
        $command->run($build, $cmd);
      }
    }
  }
}
