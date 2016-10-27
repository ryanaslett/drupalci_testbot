<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Console\Output;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * @PluginID("checkout")
 */
class Checkout extends PluginBase implements BuildStepInterface, BuildTaskInterface {

  use BuildTaskTrait;
  use FileHandlerTrait;

  /**
   * @inheritDoc
   */
  public function configure() {

    if (isset($_ENV['DCI_CoreRepository'])) {
      $this->configuration['repositories'][0]['repo'] = $_ENV['DCI_CoreRepository'];
    }
    if (isset($_ENV['DCI_CoreBranch'])) {
      $this->configuration['repositories'][0]['branch'] = $_ENV['DCI_CoreBranch'];
    }
    if (isset($_ENV['DCI_GitCheckoutDepth'])) {
      $this->configuration['repositories'][0]['depth'] = $_ENV['DCI_GitCheckoutDepth'];
    }
    if (isset($_ENV['DCI_GitCommitHash'])) {
      $this->configuration['repositories'][0]['commit_hash'] = $_ENV['DCI_GitCommitHash'];
    }
   // @TODO make a test:  $_ENV['DCI_AdditionalRepositories']='git,git://git.drupal.org/project/panels.git,8.x-3.x,modules/panels,1;git,git://git.drupal.org/project/ctools.git,8.x-3.0-alpha27,modules/ctools,1;git,git://git.drupal.org/project/layout_plugin.git,8.x-1.0-alpha23,modules/layout_plugin,1;git,git://git.drupal.org/project/page_manager.git,8.x-1.0-alpha24,modules/page_manager,1';
    if (isset($_ENV['DCI_AdditionalRepositories'])) {
      // Parse the provided repository string into it's components
      $entries = explode(';', $_ENV['DCI_AdditionalRepositories']);
      foreach ($entries as $entry) {
        if (empty($entry)) { continue; }
        $components = explode(',', $entry);
        // Ensure we have at least 3 components
        if (count($components) < 4) {
          // OPUT
          Output::writeLn("<error>Unable to parse repository information for value <options=bold>$entry</options=bold>.</error>");
          // TODO: Bail out of processing.  For now, we'll just keep going with the next entry.
          continue;
        }
        // Create the build definition entry
        $output = array(
          'protocol' => $components[0],
          'repo' => $components[1],
          'branch' => $components[2],
          'checkout_dir' => $components[3]
        );
        if (!empty($components[4])) {
          $output['depth'] = $components[4];
        }
        $this->configuration['repositories'][] = $output;
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function run(BuildInterface $build) {
    // TODO: Implement run() method.
    // OPUT
    Output::writeLn("<info>Populating container codebase data volume.</info>");
    foreach ($this->configuration['repositories'] as $repository ) {
      switch ($repository['protocol']) {
        case 'local':
          $this->setupCheckoutLocal($build, $repository);
          break;
        case 'git':
          $this->setupCheckoutGit($build, $repository);
          break;
      }
    }
    return;
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
      'repositories' => [
        [
          'protocol' => 'git',
          'repo' => 'git://drupalcode.org/project/drupal.git',
          'branch' => '8.0.x',
          'depth' => '1',
          'checkout_dir' => '.',
          'source_dir' => './',
        ]
      ],
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

  protected function setupCheckoutLocal(BuildInterface $build, $repository) {
    $source_dir = isset($repository['source_dir']) ? $repository['source_dir'] : './';
    $checkout_dir = isset($repository['checkout_dir']) ? $repository['checkout_dir'] : $build->getCodebase()->getWorkingDir();
    // TODO: Ensure we don't end up with double slashes
    // Validate source directory
    if (!is_dir($source_dir)) {
      // OPUT
      Output::error("Directory error", "The source directory <info>$source_dir</info> does not exist.");

      return;
    }
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validateDirectory($build, $checkout_dir))) {
      // Invalidate checkout directory
      // OPUT
      Output::error("Directory error", "The checkout directory <info>$directory</info> is invalid.");

      return;
    }
    // OPUT
    Output::writeln("<comment>Copying files from <options=bold>$source_dir</options=bold> to the local checkout directory <options=bold>$directory</options=bold> ... </comment>");
    // TODO: Make sure target directory is empty
    #  $this->exec("cp -r $source_dir/. $directory", $cmdoutput, $result);
    $exclude_var = isset($repository['DCI_EXCLUDE']) ? '--exclude="' . $repository['DCI_EXCLUDE'] . '"' : "";
    $this->exec("rsync -a $exclude_var  $source_dir/. $directory", $cmdoutput, $result);
    if ($result !== 0) {
      // OPUT
      Output::error("Copy error", "Error encountered while attempting to copy code to the local checkout directory.");

      return;
    }
    // OPUT
    Output::writeLn("<comment>DONE</comment>");
  }

  protected function setupCheckoutGit(BuildInterface $build, $repository) {
    // OPUT
    Output::writeLn("<info>Entering setup_checkout_git().</info>");
    // @TODO: these should always have a default. no sense in setting them here.
    $repo = isset($repository['repo']) ? $repository['repo'] : 'git://drupalcode.org/project/drupal.git';

    $git_branch = isset($repository['branch']) ? $repository['branch'] : 'master';
    $checkout_directory = isset($repository['checkout_dir']) ? $repository['checkout_dir'] : $build->getCodebase()->getWorkingDir();
    // TODO: Ensure we don't end up with double slashes
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validateDirectory($build, $checkout_directory))) {
      // Invalid checkout directory
      // OPUT
      Output::error("Directory Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }
    if (substr($repository['repo'],0,4) == 'file') {
      // If the repository is specified as a local file://tmp/project, then we rsync the
      // project over to avoid re-composering and re-cloning.
      $exclude_var = isset($repository['DCI_EXCLUDE']) ? '--exclude="' . $repository['DCI_EXCLUDE'] . '"' : "";
      $source_dir = substr($repository['repo'],7);
      $cmd = "rsync -a $exclude_var  $source_dir/. $directory";
      // OPUT
      Output::writeLn("<comment>Performing rsync of git checkout of $repo $git_branch branch to $directory.</comment>");
      Output::writeLn("Rsync Command: $cmd");
      $this->exec($cmd, $cmdoutput, $result);

      if ($result !== 0) {
        // Git threw an error.
        // OPUT
        Output::error("Checkout Error", "The rsync returned an error.  Error Code: $result");

        return;
      }

      $cmd = "cd $directory; git clean -fx; git checkout -f $git_branch";
      $this->exec($cmd, $cmdoutput, $result);

      if ($result !== 0) {
        // Git threw an error.
        // OPUT
        Output::error("Checkout Error", "Unable to change branch.  Error Code: $result");
        return;
      }
    } else {
      // OPUT
      Output::writeLn("<comment>Performing git checkout of $repo $git_branch branch to $directory.</comment>");
      // TODO: Make sure target directory is empty
      $git_depth = '';
      if (isset($repository['depth']) && empty($repository['commit_hash'])) {
        $git_depth = '--depth ' . $repository['depth'];
      }
      $cmd = "git clone -b $git_branch $git_depth $repo '$directory'";
      // OPUT
      Output::writeLn("Git Command: $cmd");
      $this->exec($cmd, $cmdoutput, $result);

      if ($result !== 0) {
        // Git threw an error.
        // OPUT
        Output::error("Checkout Error", "The git checkout returned an error.  Error Code: $result");
        return;
      }
    }

    if (!empty($repository['commit_hash'])) {
      $cmd =  "cd " . $directory . " && git reset -q --hard " . $repository['commit_hash'] . " ";
      // OPUT
      Output::writeLn("Git Command: $cmd");
      $this->exec($cmd, $cmdoutput, $result);
    }
    if ($result !==0) {
      // Git threw an error.
      $build->errorOutput("Checkout failed", "The git checkout returned an error.");
      // TODO: Pass on the actual return value for the git checkout
      return;
    }

    $cmd = "cd '$directory' && git log --oneline -n 1 --decorate";
    $this->exec($cmd, $cmdoutput, $result);
    // OPUT
    Output::writeLn("<comment>Git commit info:</comment>");
    Output::writeLn("<comment>\t" . implode($cmdoutput));

    Output::writeLn("<comment>Checkout complete.</comment>");
  }


}
