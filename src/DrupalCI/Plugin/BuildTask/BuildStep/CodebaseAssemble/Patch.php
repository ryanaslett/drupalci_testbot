<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Console\Output;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Build\Codebase\PatchInterface;
use DrupalCI\Build\Codebase\Patch as PatchFile;

/**
 * @PluginID("patch")
 */
class Patch extends PluginBase implements BuildStepInterface, BuildTaskInterface {

  use BuildTaskTrait;
  use FileHandlerTrait;

  /**
   * @inheritDoc
   */
  public function configure() {
    // @TODO make into a test
    // $_ENV['DCI_Patch']='https://www.drupal.org/files/issues/2796581-region-136.patch,.;https://www.drupal.org/files/issues/another.patch,.';
    if (isset($_ENV['DCI_Patch'])) {
      $this->configuration['patches'] = $this->process($_ENV['DCI_Patch']);
    }
  }

  /**
   * @inheritDoc
   */
  public function run(BuildInterface $build) {

    $files = $this->configuration['patches'];

    $codebase = $build->getCodebase();
    if (empty($files)) {
      // OPUT
      Output::writeLn('No patches to apply.');
    }
    foreach ($files as $key => $details) {
      if (empty($details['from'])) {
        // OPUT
        Output::error("Patch error", "No valid patch file provided for the patch command.");

        return;
      }
      // Create a new patch object
      $patch = new PatchFile($details, $codebase);
      // Validate our patch's source file and target directory
      if (!$patch->validate()) {

        return;
      }

      // Apply the patch
      if (!$patch->apply()) {


        // Hack to create a xml file for processing by Jenkins.
        // TODO: Remove once proper build failure processing is in place

        // Save an xmlfile to the jenkins artifact directory.
        // find jenkins artifact dir
        //
        $source_dir = $build->getCodebase()->getWorkingDir();
        // TODO: Temporary hack.  Strip /checkout off the directory
        $artifact_dir = preg_replace('#/checkout$#', '', $source_dir);

        // Set up output directory (inside working directory)
        $output_directory = $artifact_dir . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . 'xml';

        if (!is_dir($output_directory)) {
          mkdir($output_directory, 0777, TRUE);
        }

        $output = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '�', implode("\n", $patch->getPatchApplyResults()));

        $xml_error = '<?xml version="1.0"?>

                      <testsuite errors="1" failures="0" name="Error: Patch failed to apply" tests="1">
                        <testcase classname="Apply Patch" name="' . $patch->getLocalSource() . '">
                          <error message="Patch Failed to apply" type="PatchFailure">Patch failed to apply</error>
                        </testcase>
                        <system-out><![CDATA[' . $output . ']]></system-out>
                      </testsuite>';
        // ENVIRONMENT - junit xml output directory
        file_put_contents($output_directory . "/patchfailure.xml", $xml_error);

        return;
      };
      // Update our list of modified files
      $codebase->addModifiedFiles($patch->getModifiedFiles());
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
      'patches' => [],
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
