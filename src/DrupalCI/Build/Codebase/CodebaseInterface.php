<?php

namespace DrupalCI\Build\Codebase;

interface CodebaseInterface {
  // ENVIRONMENT - Host working dir
  public function setSourceDir($source_dir);

  // ENVIRONMENT - Host working dir
  public function getSourceDir();

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

}
