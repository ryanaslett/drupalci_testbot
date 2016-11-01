<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Basic test that proves that drupalci can execute a simpletest and generate a result
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group docker
 *
 * @see TESTING.md
 */
class CoreD7PassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */

  protected $dciConfig = [
    'DCI_CoreBranch=7.x',
    'DCI_CoreRepository=file:///var/lib/drupalci/drupal-checkout',
    'DCI_JobType=simpletestlegacy7',
    'DCI_GitCommitHash=3d5bcd3',
    'DCI_RunScript=/var/www/html/scripts/run-tests.sh',
    'DCI_TestGroups=Syslog',
    'DCI_PHPVersion=7',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
  ];

  public function testCoreD7Passes() {

    $this->setUp();
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $build = $this->getCommand('run')->getBuild();
    $this->assertRegExp('/.*simpletestlegacy7*/', $app_tester->getDisplay());
    $this->assertRegExp('/.*Syslog functionality 17 passes, 0 fails, and 0 exceptions*/', $app_tester->getDisplay());
    // Look for junit xml results file
    $output_file = $build->getXmlDirectory() . "/testresults.xml";
    $this->assertFileExists($output_file);
    // create a test fixture that contains the xml output results.
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/CoreD7PassingTest_testresults.xml', $output_file);
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
