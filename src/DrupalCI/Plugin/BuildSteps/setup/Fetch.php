<?php

namespace DrupalCI\Plugin\BuildSteps\setup;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Console\Output;
use DrupalCI\Plugin\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskTrait;
use GuzzleHttp\Client;

/**
 * Processes "setup: fetch:" instructions from within a build definition.
 *
 * @PluginID("fetch")
 *
 * @todo This task uses a string to specify multiple files and their
 *   destinations. Improve this to use some kind of more structured data.
 */
class Fetch extends FileHandlerBase implements BuildTaskInterface {

  use BuildTaskTrait;

  public function getDefaultConfiguration() {
    return [
      'DCI_Fetch' => '',
    ];
  }

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $build, &$config) {
    $config = $this->resolveDciVariables($config);
    $files = $this->process($config['files']);

    if (empty($files)) {
      Output::writeLn('No files to fetch.');
    }
    foreach ($files as $details) {
      // URL and target directory
      // TODO: Ensure $details contains all required parameters
      if (empty($details['from'])) {
        Output::error("Fetch error", "No valid target file provided for fetch command.");
        $build->error();
        return;
      }
      $url = $details['from'];
      $workingdir = $build->getCodebase()->getWorkingDir();
      $fetchdir = (!empty($details['to'])) ? $details['to'] : $workingdir;
      if (!($directory = $this->validateDirectory($build, $fetchdir))) {
        // Invalid checkout directory
        Output:error("Fetch error", "The fetch directory <info>$directory</info> is invalid.");
        $build->error();
        return;
      }
      $info = pathinfo($url);
      try {
        $destination_file = $directory . "/" . $info['basename'];
        $this->httpClient()
          ->get($url, ['save_to' => $destination_file]);
      }
      catch (\Exception $e) {
        Output::error("Write error", "An error was encountered while attempting to write <info>$url</info> to <info>$directory</info>");
        $build->error();
        return;
      }
      Output::writeLn("<comment>Fetch of <options=bold>$url</options=bold> to <options=bold>$destination_file</options=bold> complete.</comment>");
    }
  }

  /**
   * @return \GuzzleHttp\ClientInterface
   */
  protected function httpClient() {
    if (!isset($this->httpClient)) {
      $this->httpClient = new Client;
    }
    return $this->httpClient;
  }

}
