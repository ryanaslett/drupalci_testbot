<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\setup\Patch
 *
 * Processes "setup: patch:" instructions from within a build definition.
 */

namespace DrupalCI\Plugin\BuildSteps\setup;

use DrupalCI\Console\Output;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Codebase\Patch as PatchFile;
use DrupalCI\Plugin\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskTrait;

/**
 * @PluginID("patch")
 */
class Patch extends FileHandlerBase implements BuildTaskInterface {

  use BuildTaskTrait;

  public function getDefaultConfiguration() {
    return [
      'DCI_Patch' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, &$config) {
    $config = $this->resolveDciVariables($config);

    $files = $this->process($config['files']);

    $codebase = $build->getCodebase();
    if (empty($files)) {
      Output::writeLn('No patches to apply.');
    }
    foreach ($files as $key => $details) {
      if (empty($details['from'])) {
        Output::error("Patch error", "No valid patch file provided for the patch command.");
        $build->error();
        return;
      }
      // Create a new patch object
      $patch = new PatchFile($details, $codebase);
      // Validate our patch's source file and target directory
      if (!$patch->validate()) {
        $build->error();
        return;
      }

      // Apply the patch
      if (!$patch->apply()) {
        $build->error();

        // Hack to create a xml file for processing by Jenkins.
        // TODO: Remove once proper build failure processing is in place

        // Save an xmlfile to the jenkins artifact directory.
        // find jenkins artifact dir
        //
        $source_dir = $build->getCodebase()->getWorkingDir();
        // TODO: Temporary hack.  Strip /checkout off the directory
        $artifact_dir = preg_replace('#/checkout$#', '', $source_dir);

        // Set up output directory (inside working directory)
        $output_directory = $artifact_dir . DIRECTORY_SEPARATOR . 'artifacts' . DIRECTORY_SEPARATOR . $build->getBuildVar('DCI_JunitXml');

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
        file_put_contents($output_directory . "/patchfailure.xml", $xml_error);

        return;
      };
      // Update our list of modified files
      $codebase->addModifiedFiles($patch->getModifiedFiles());
    }
  }
}
