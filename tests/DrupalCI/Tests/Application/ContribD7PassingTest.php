<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test a passing d7 contrib test.
 *
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class ContribD7PassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/forena.git,7.x-4.x,sites/all/modules/forena,1;',
    'DCI_ComposerInstall=true',
    'DCI_CoreBranch=7.x',
    'DCI_CoreRepository=file:///tmp/drupal',
    'DCI_DBVersion=mysql-5.5',
    'DCI_GitCommitHash=d33ac7e',
    'DCI_JobType=simpletestlegacy7',
    'DCI_PHPVersion=5.3',
    'DCI_RunScript=/var/www/html/scripts/run-tests.sh',
    'DCI_TestItem=directory:sites/all/modules/forena',
  ];

  public function testD7Contrib() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/.*simpletestlegacy7*/', $app_tester->getDisplay());
    $this->assertRegExp('/Forena Reports 15 passes, 0 fails, and 0 exceptions/', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
