<?php

/**
 * @file
 * Contains \DrupalCI\Build\Codebase\Patch
 */

namespace DrupalCI\Build\Codebase;

use DrupalCI\Console\Output;
use DrupalCI\Build\Codebase\Codebase;
use DrupalCI\Injectable;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Pimple\Container;

/**
 * Class Patch
 * @package DrupalCI\Build\Codebase
 */
class Patch implements PatchInterface, Injectable {

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  /**
   * Local or Remote Patch File
   *
   * @var string
   */
  protected $type = 'remote';

  public function inject(Container $container) {
    $this->io = $container['console.io'];
  }

  /**
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @param string $type
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * Source patch location
   *
   * @var string
   */
  protected $source;

  /**
   * @return string
   */
  public function getSource()
  {
    return $this->source;
  }

  /**
   * @param string $source
   */
  protected function setSource($source)
  {
    $this->source = $source;
  }

  /**
   * Source patch location on the local file system
   *
   * @var string
   */
  protected $local_source;

  /**
   * @return string
   */
  public function getLocalSource()
  {
    return $this->local_source;
  }

  /**
   * @param string $local_source
   */
  protected function setLocalSource($local_source)
  {
    $this->local_source = $local_source;
  }

  /**
   * Target patch application directory
   *
   * @var string
   */
  protected $apply_dir;

  /**
   * @return string
   */
  public function getApplyDir()
  {
    return $this->apply_dir;
  }

  /**
   * @param string $apply_dir
   */
  protected function setApplyDir($apply_dir)
  {
    $this->apply_dir = $apply_dir;
  }

  /**
   * @return string
   */
  public function getPatchApplyResults() {
    return $this->patch_apply_results;
  }

  /**
   * @param string $patch_apply_results
   */
  public function setPatchApplyResults($patch_apply_results) {
    $this->patch_apply_results = $patch_apply_results;
  }

  /**
   * "Patch has been applied" flag
   *
   * @var bool
   */
  protected $applied;

  /**
   * List of files modified by this patch
   *
   * @var array
   */
  protected $modified_files;

  /**
   * Base Working directory
   *
   * @var string
   */
  protected $working_dir;

  /**
   * Results from applying a patch
   *
   * @var string
   */
  protected $patch_apply_results;


  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @param string[] $patch_details
   * @param string $source_dir
   */
  public function __construct($patch_details, $source_dir)
  {
    // Copy working directory from the initial codebase

    $this->working_dir = $source_dir;

    // Set source and apply_dir properties
    $this->setSource($patch_details['from']);
    $this->setApplyDir($patch_details['to']);

    // Determine whether passed a URL or local file
    $type = filter_var($patch_details['from'], FILTER_VALIDATE_URL) ? "remote" : "local";
    $this->setType($type);

    // If a remote file, download a local copy
    if ($type == "remote") {
      // Download the patch file
      // If any errors encountered during download, we expect guzzle to throw
      // an appropriate exception.
      $local_source = $this->download();
    } else {
      // If a local file, we already know the local source location
      $local_source = $this->working_dir . '/' . $patch_details['to'] . '/' . $patch_details['from'];
    }
    $this->setLocalSource($local_source);

    // Set initial 'applied' state
    $this->applied = false;

  }

  /**
   * Obtain remote patch file
   *
   * @return string
   */
  protected function download()
  {
    $url = $this->getSource();
    $file_info = pathinfo($url);
    $directory = $this->working_dir;

    $destination_file = $directory . '/' . $file_info['basename'];
    $this->httpClient()
      ->get($url, ['save_to' => "$destination_file"]);
    $this->io->writeln("<info>Patch downloaded to <options=bold>$destination_file</options=bold></info>");
    return $destination_file;
  }

  /**
   * Validate patch file and target directory
   *
   * @return bool
   */
  public function validate()
  {
    if ($this->validate_file() && $this->validate_target()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate file exists
   *
   * @return bool
   */
  public function validate_file()
  {
    $source = $this->getLocalSource();
    $real_file = realpath($source);
    if ($real_file === FALSE) {
      // Invalid patch file
      $this->io->drupalCIError("Patch Error", "The patch file <info>$source</info> is invalid.");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Validate target directory exists
   *
   * @return bool
   */
  public function validate_target()
  {
    $apply_dir = $this->working_dir . '/' . $this->getApplyDir();
    $real_directory = realpath($apply_dir);
    if ($real_directory === FALSE) {
      // Invalid target directory
      $this->io->drupalCIError("Patch Error", "The target patch directory <info>$apply_dir</info> is invalid.");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Apply the patch
   *
   * @return bool
   */
  public function apply()
  {

    $source = realpath($this->getLocalSource());
    $target = realpath($this->working_dir . '/' . $this->getApplyDir());

    $cmd = "cd $target && git apply -p1 $source 2>&1";

    exec($cmd, $cmdoutput, $result);
    $this->setPatchApplyResults($cmdoutput);
    if ($result !== 0) {
      // The command threw an error.
      $this->io->writeLn($cmdoutput);
      $this->io->drupalCIError("Patch Error", "The patch attempt returned an error.  Error code: $result");
      // TODO: Pass on the actual return value for the patch attempt
      return $result;
    }
    $this->io->writeLn("<comment>Patch <options=bold>$source</options=bold> applied to directory <options=bold>$target</options=bold></comment>");
    $this->applied = TRUE;
    return $result;
  }

  /**
   * Retrieves the files modified by this patch
   *
   * @return array|bool
   */
  public function getModifiedFiles()
  {
    // Only calculate the modified files if the patch has been applied.
    if (!$this->applied) {
      return [];
    }
    if (empty($this->modified_files)) {
      // Calculate modified files

      $apply_dir = $this->working_dir . '/' . $this->getApplyDir();
      // TODO: refactor this exec out of here.
      $cmd = "cd $apply_dir && git diff --name-only";
      exec($cmd, $cmdoutput, $return);
      if ($return !== 0) {
        // git diff returned a non-zero error code
        $this->io->writeln("<error>Git diff command returned a non-zero code while attempting to parse modified files. (Return Code: $return)</error>");
        return FALSE;
      }
      $files = $cmdoutput;

      $this->modified_files = array();
      foreach ($files as $file) {
        $this->modified_files[] = $this->getApplyDir(). '/' . $file;
      }
    }
    return $this->modified_files;
  }

  /**
   * @return \GuzzleHttp\ClientInterface
   */
  protected function httpClient()
  {
    if (!isset($this->httpClient)) {
      $this->httpClient = new Client;
    }
    return $this->httpClient;
  }
}
