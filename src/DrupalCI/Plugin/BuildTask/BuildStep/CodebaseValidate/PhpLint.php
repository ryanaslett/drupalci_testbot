<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseValidate;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Console\Output;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("phplint")
 */
class PhpLint extends PluginBase implements BuildStepInterface, BuildTaskInterface {

  use BuildTaskTrait;

  /* @var  \DrupalCI\Build\Environment\EnvironmentInterface */
  protected $environment;

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  /**
   * The current build.
   *
   * @var \DrupalCI\Build\BuildInterface
   */
  protected $build;


  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
    $this->codebase = $container['codebase'];
    $this->build = $container['build'];
  }

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
  public function run() {
    // TODO Throw a BuildException if there are syntax errors.
    $this->io->writeln('<info>SyntaxCheck checking for php syntax errors.</info>');

    $modified_files = $this->codebase->getModifiedFiles();

    if (empty($modified_files)) {
      return 0;
    }

    $workingdir = $this->build->getSourceDirectory();
    $concurrency = $this->configuration['concurrency'];
    $bash_array = "";
    foreach ($modified_files as $file) {
      $file_path = $workingdir . "/" . $file;
      // Checking for: if not in a vendor dir, if the file still exists, and if the first 32 (length - 1) bytes of the file contain <?php
      if ((strpos($file, '/vendor/') === FALSE) && file_exists($file_path) && (strpos(fgets(fopen($file_path, 'r'), 33), '<?php') !== FALSE)) {
        $bash_array .= "$file\n";
      }
    }

    $lintable_files = $this->build->getArtifactDirectory() .'/lintable_files.txt';
    $this->io->writeln("<info>" . $lintable_files . "</info>");
    file_put_contents($lintable_files, $bash_array);
    // Make sure
    if (0 < filesize($lintable_files)) {
      // TODO: Remove hardcoded /var/www/html.
      // This should be come Codebase->getLocalDir() or similar
      // Use xargs to concurrently run linting on file.
      $cmd = "cd /var/www/html && xargs -P $concurrency -a $lintable_files -I {} php -l '{}'";
      $this->environment->executeCommands($cmd);
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
