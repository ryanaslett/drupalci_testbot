<?php

/**
 * @file
 * Contains \DrupalCI\Build\Results\Artifacts\BuildArtifact.
 */

namespace DrupalCI\Build\Results\Artifacts;

/**
 * Class BuildArtifact
 * @package DrupalCI\Build\Results\Artifacts
 *
 * Defines a build artifact for a given build.
 */
class BuildArtifact {

  // Valid build artifact types include file, directory, or string.
  protected $type;
  public function setType($type) { $this->type = $type; }
  public function getType() { return $this->type; }

  // Value contains the file/directory location, or the actual string content.
  protected $value;
  public function setValue($value) { $this->value = $value; }
  public function getValue() { return $this->value; }

  public function __construct($type, $value) {
    $this->setType($type);
    $this->setValue($value);
  }

}
