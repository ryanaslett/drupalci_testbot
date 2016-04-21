<?php

/**
 * @file
 * DrupalCI Config helper class.
 */

namespace DrupalCI\Tests\Console\Helpers;

use DrupalCI\Console\Helpers\ConfigHelper;
use DrupalCI\Tests\DrupalCITestCase;

class ConfigHelperTest extends DrupalCITestCase {

  private $origHome;
  private $tmp_home;
  private $dci_config;
  private $data = [
    'DCI_PHPVersion' => '7',
    'DCI_Concurrency' => '2',
    'DCI_CoreRepository' => 'git://git.drupal.org/project/drupal.git',
    'DCI_TestGroups' => 'Url',
  ];

  public function setUp(){
    $this->tmp_home = "/tmp/confighelpertest";
    putenv('HOME='.$this->tmp_home);
    mkdir($this->tmp_home . '/.drupalci', 0775, true);
    $this->dci_config = $this->tmp_home . '/.drupalci/config';
    copy(__DIR__ . '/Fixtures/ConfigHelperTest_config', $this->dci_config);
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

  public function testGetCurrentConfigSetContents() {
    $confighelper = new ConfigHelper();
    $results = $confighelper->getCurrentConfigSetContents();
    $r2 = '';
    foreach ($results as $key => $value) {
      $r2 .= $value . PHP_EOL;
    }
    $this->assertFileExists($this->dci_config);
    $this->assertStringEqualsFile(__DIR__ . '/Fixtures/ConfigHelperTest_config', $r2, 'The file has been altered.');

  }

  protected function tearDown(){
    unlink($this->tmp_home . '/.drupalci/config');
    rmdir($this->tmp_home . '/.drupalci');
    rmdir($this->tmp_home);
  }
}
