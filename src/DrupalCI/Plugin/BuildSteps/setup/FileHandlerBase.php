<?php

namespace DrupalCI\Plugin\BuildSteps\setup;

use DrupalCI\Plugin\BuildSteps\setup\SetupBase;

abstract class FileHandlerBase extends SetupBase {

  /**
   * Process the DCI_Fetch/DCI_Patch variables.
   *
   * Takes a string defining files to be fetched or applied, and converts this
   * to an array with a from and a to key.
   *
   * Input format: (string) $value = "http://example.com/file1.patch,destination_directory1;[http://example.com/file2.patch,destination_directory2];..."
   * Desired Result: [
   * array('url' => 'http://example.com/file1.patch', 'fetch_directory' => 'fetch_directory1')
   * array('url' => 'http://example.com/file2.patch', 'fetch_directory' => 'fetch_directory2')
   *      ...   ]
   */
  protected function process($value) {
    $data = [];
    foreach (explode(';', $value) as $file_string) {
      if (!empty($file_string)) {
        $file = [];
        if (strpos($file_string, ',') === FALSE) {
          $file['from'] = $file_string;
          $file['to'] = '.';
        }
        else {
          $elements = explode(',', $file_string);
          $file['from'] = $elements[0];
          $file['to'] = (!empty($elements[1])) ? $elements[1] : '.';
        }
        $data[] = $file;
      }
    }
    return $data;
  }
}