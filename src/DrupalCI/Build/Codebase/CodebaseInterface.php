<?php

namespace DrupalCI\Build\Codebase;

interface CodebaseInterface {
  // ENVIRONMENT - Host working dir
  public function setSourceDir($source_dir);

  // ENVIRONMENT - Host working dir
  public function getSourceDir();

  public function getPatches();

  public function setPatches($patches);

  public function addPatch(Patch $patch);

  public function getModifiedFiles();

  public function addModifiedFile($filename);

  public function addModifiedFiles($files);

}
