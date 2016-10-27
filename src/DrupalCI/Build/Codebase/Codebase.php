<?php

/**
 * @file
 * Contains \DrupalCI\Build\Codebase\Codebase
 */

namespace DrupalCI\Build\Codebase;

use DrupalCI\Console\Output;
use DrupalCI\Build\Codebase\Patch;
use DrupalCI\Build\Definition\BuildDefinition;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use Pimple\Container;
// CODEBASE
class Codebase implements CodebaseInterface {

  /**
   * The base working directory for this codebase build
   *
   * @var string
   */
  // ENVIRONMENT - root directory of the codebase on the HOST
  protected $working_dir;
  public function setWorkingDir($working_dir) {  $this->working_dir = $working_dir;  }
  public function getWorkingDir() {  return $this->working_dir;  }

  /**
   * The core project for this build (e.g. Drupal)
   *
   * @var string
   */
  protected $core_project;
  public function getCoreProject()  {  return $this->core_project;  }
  public function setCoreProject($core_project) { $this->core_project = $core_project; }

  /**
   * The specific version of the core project (e.g. 8.0.x)
   *
   * @var string
   */
  protected $core_version;
  public function getCoreVersion() {  return $this->core_version;  }
  public function setCoreVersion($core_version) {  $this->core_version = $core_version;  }

  /**
   * The major version of the core project (e.g. 8)
   *
   * @var string
   */
  protected $core_major_version;
  public function getCoreMajorVersion() {  return $this->core_major_version;  }
  public function setCoreMajorVersion($core_major_version) {  $this->core_major_version = $core_major_version;  }

  /**
   * Any patches used to generate this codebase
   *
   * @var \DrupalCI\Build\Codebase\Patch[]
   */
  protected $patches;
  public function getPatches() { return $this->patches;  }
  public function setPatches($patches) {  $this->patches = $patches;  }
  public function addPatch(Patch $patch) {
    if (!empty($this->patches) && !in_array($patch, $this->patches)) {
      $this->patches[] = $patch;
    }
  }

  /**
   * A storage variable for any modified files
   */
  protected $modified_files = [];
  public function getModifiedFiles() {  return $this->modified_files;  }
  public function addModifiedFile($filename) {
    if (!is_array($this->modified_files)) { $this->modified_files = []; }
    if (!in_array($filename, $this->modified_files)) { $this->modified_files[] = $filename;  }
  }
  public function addModifiedFiles($files) {
    foreach ($files as $file) {
      $this->addModifiedFile($file);
    }
  }

  protected function determineMajorVersion($version) {
    $pattern = "/^(\d+)/";
    if (preg_match($pattern, $version, $matches)) {
      return $matches[0];
    }
    return NULL;
  }

  /**
   * Initialize Codebase
   */
  // ENVIRONMENT - Working Directory
  /**
   * @param $build_id
   *
   * @return bool
   */
  public function setupWorkingDirectory($build_id) {
    // BROKEN need to make setupWorkingDirectory not use getDCIVariable.
    // Check if the target working directory has been specified.
   // $working_dir = $build_definition->getDCIVariable('DCI_WorkingDir');
    $tmp_directory = sys_get_temp_dir();

    // Generate a default directory name if none specified
    if (empty($working_dir)) {
      // Case:  No explicit working directory defined.
      $working_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $build_id;
    }
    else {
      // We force the working directory to always be under the system temp dir.
      if (strpos($working_dir, realpath($tmp_directory)) !== 0) {
        if (substr($working_dir, 0, 1) == DIRECTORY_SEPARATOR) {
          $working_dir = $tmp_directory . $working_dir;
        }
        else {
          $working_dir = $tmp_directory . DIRECTORY_SEPARATOR . $working_dir;
        }
      }
    }
    // Create directory if it doesn't already exist
    if (!is_dir($working_dir)) {
      $result = mkdir($working_dir, 0777, TRUE);
      if (!$result) {
        // Error creating checkout directory
        // OPUT
        Output::error('Directory Creation Error', 'Error encountered while attempting to create local working directory');
        return FALSE;
      }
      // OPUT
      Output::writeLn("<info>Checkout directory created at <options=bold>$working_dir</options=bold></info>");
    }

    // Validate that the working directory is empty.  If the directory contains
    // an existing git repository, for example, our checkout attempts will fail
    // TODO: Prompt the user to ask if they'd like to overwrite
    $iterator = new \FilesystemIterator($working_dir);
    if ($iterator->valid()) {
      // Existing files found in directory.
      // OPUT
      Output::error('Directory not empty', 'Unable to use a non-empty working directory.');
      return FALSE;
    };

    // Convert to the full path and ensure our directory is still valid
    $working_dir = realpath($working_dir);
    if (!$working_dir) {
      // Directory not found after conversion to canonicalized absolute path
      // OPUT
      Output::error('Directory not found', 'Unable to determine working directory absolute path.');
      return FALSE;
    }

    // Ensure we're still within the system temp directory
    if (strpos(realpath($working_dir), realpath($tmp_directory)) !== 0) {
      // OPUT
      Output::error('Directory error', 'Detected attempt to traverse out of the system temp directory.');
      return FALSE;
    }

    // If we arrive here, we have a valid empty working directory.
    $this->setWorkingDir($working_dir);
    return TRUE;
  }
}
