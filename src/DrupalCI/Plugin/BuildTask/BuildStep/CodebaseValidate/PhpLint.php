<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\ContainerCommand;
use DrupalCI\Console\Output;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * @PluginID("phplint")
 */
class PhpLint extends PluginBase implements BuildStepInterface, BuildTaskInterface {

  use BuildTaskTrait;

  /**
   * @inheritDoc
   */
  public function configure() {
    if (isset($_ENV['DCI_Concurrency'])) {
      $this->configuration['concurrency']= $_ENV['DCI_Concurrency'];
    }
  }

  /**
   * @inheritDoc
   */
  public function run(BuildInterface $build) {
    $this->io->writeln('<info>SyntaxCheck checking for php syntax errors.</info>');

    // CODEBASE
    $codebase = $build->getCodebase();
    $modified_files = $codebase->getModifiedFiles();

    if (empty($modified_files)) {
      return;
    }

    // ENVIRONMENT - codebase working dir
    $workingdir = $codebase->getWorkingDir();
    $concurrency = $this->configuration['concurrency'];
    $bash_array = "";
    foreach ($modified_files as $file) {
      $file_path = $workingdir . "/" . $file;
      // Checking for: if in a vendor dir, if the file still exists, or if the first 32 (length - 1) bytes of the file contain <?php
      if ((strpos($file, '/vendor/') === FALSE) && file_exists($file_path) && (strpos(fgets(fopen($file_path, 'r'), 33), '<?php') !== FALSE)) {
        $bash_array .= "$file\n";
      }
    }

    // ENVIRONMENT - artifact directory.
    $lintable_files = 'artifacts/lintable_files.txt';
    $this->io->writeln("<info>" . $workingdir . "/" . $lintable_files . "</info>");
    file_put_contents($workingdir . "/" . $lintable_files, $bash_array);
    // Make sure
    if (0 < filesize($workingdir . "/" . $lintable_files)) {
      // TODO: Remove hardcoded /var/www/html.
      // This should be come Codebase->getLocalDir() or similar
      // Use xargs to concurrently run linting on file.
      $cmd = "cd /var/www/html && xargs -P $concurrency -a $lintable_files -I {} php -l '{}'";
      $command = new ContainerCommand();
      $command->inject($this->container);
      $command->run($build, $cmd);
    }
  }

  /**
   * @inheritDoc
   */
  public function complete() {
    // TODO: Implement complete() method.
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'concurrency' => '4',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getChildTasks() {
    // TODO: Implement getChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function setChildTasks($buildTasks) {
    // TODO: Implement setChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function getShortError() {
    // TODO: Implement getShortError() method.
  }

  /**
   * @inheritDoc
   */
  public function getErrorDetails() {
    // TODO: Implement getErrorDetails() method.
  }

  /**
   * @inheritDoc
   */
  public function getResultCode() {
    // TODO: Implement getResultCode() method.
  }

  /**
   * @inheritDoc
   */
  public function getArtifacts() {
    // TODO: Implement getArtifacts() method.
  }

}
