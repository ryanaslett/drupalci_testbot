<?php
/**
 * Created by PhpStorm.
 * User: Ryan
 * Date: 10/9/16
 * Time: 10:41 AM
 */
namespace DrupalCI\Build\Codebase;

use DrupalCI\Build\Definition\BuildDefinition;

interface CodeBaseInterface {
  public function setWorkingDir($working_dir);

  public function getWorkingDir();

  public function getCoreProject();

  public function setCoreProject($core_project);

  public function getCoreVersion();

  public function setCoreVersion($core_version);

  public function getCoreMajorVersion();

  public function setCoreMajorVersion($core_major_version);

  public function getPatches();

  public function setPatches($patches);

  public function addPatch(Patch $patch);

  public function getModifiedFiles();

  public function addModifiedFile($filename);

  public function addModifiedFiles($files);

  /**
   * @param \DrupalCI\Build\Definition\BuildDefinition $job_definition
   */
  public function setupProject(BuildDefinition $job_definition);

  /**
   * Initialize Codebase
   */
  public function setupWorkingDirectory(BuildDefinition $job_definition);
}
