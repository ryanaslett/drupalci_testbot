<?php

namespace DrupalCI\Build\Codebase;

use DrupalCI\Build\Definition\BuildDefinition;

interface CodebaseInterface {
  // ENVIRONMENT - Host working dir
  public function setWorkingDir($working_dir);

  // ENVIRONMENT - Host working dir
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
   * @param \DrupalCI\Build\Definition\BuildDefinition $build_definition
   */
  public function setupProject(BuildDefinition $build_definition);

  /**
   * Initialize Codebase
   *
   * @param $buildid
   *
   * Takes in the build_id and creates the working directory.
   *
   */
  // ENVIRONMENT - Host working dir
  public function setupWorkingDirectory($buildid);
}
