<?php

/**
 * @file
 * DrupalCI Config helper class.
 */

namespace DrupalCI\Tests\Console\Helpers;

use DrupalCI\Helpers\ConfigHelper;
use DrupalCI\Tests\DrupalCITestCase;

class ConfigHelperTest extends DrupalCITestCase {

  // TODO: This goes away once the env's are gathered directly in the build
  // steps.
  private $data = [
    'DCI_PHPVersion' => '7',
    'DCI_Concurrency' => '2',
    'DCI_CoreRepository' => 'git://git.drupal.org/project/drupal.git',
    'DCI_TestGroups' => 'Url',
  ];

  public function setUp(){
    foreach ($this->data as $key => $value) {
      $_ENV[$key] = $value;
    }
  }

  public function testGetCurrentEnvVars() {
    $confighelper = new ConfigHelper();
    $results = $confighelper->getCurrentEnvVars();
    foreach ($this->data as $key => $value) {
      $this->assertEquals( $value, $results[$key]);
    }
  }
}
