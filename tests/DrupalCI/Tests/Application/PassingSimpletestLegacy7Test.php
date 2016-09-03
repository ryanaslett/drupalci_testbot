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
class PassingSimpletestLegacy7Test extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */

  protected $dciConfig = [
    'DCI_CoreBranch=7.x',
    'DCI_CoreRepository=file:///tmp/drupal',
    'DCI_JobType=simpletestlegacy7',
    'DCI_JunitXml=xml',
    'DCI_GitCommitHash=3d5bcd3',
    'DCI_RunScript=/var/www/html/scripts/run-tests.sh',
    'DCI_TestGroups=Syslog',
    'DCI_PHPVersion=7',
    'DCI_DBVersion=mysql-5.5',
  ];

  public function testBasicTest() {

    $this->setUp();
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $job = $this->getCommand('run')->getJob();
    $this->assertRegExp('/.*simpletestlegacy7*/', $app_tester->getDisplay());
    $this->assertRegExp('/.*Syslog functionality 17 passes, 0 fails, and 0 exceptions*/', $app_tester->getDisplay());
    // Look for junit xml results file
    $output_file = $job->getJobCodebase()
        ->getWorkingDir() . "/artifacts/" . $job->getBuildVars()["DCI_JunitXml"] . '/testresults.xml';
    $this->assertFileExists($output_file);
    // create a test fixture that contains the xml output results.
    //$this->assertFileEquals();
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/PassingSimpletestLegacy7Test_testresults.xml', $output_file);

  }
}
