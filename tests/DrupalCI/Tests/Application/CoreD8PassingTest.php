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
class CoreD8PassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */

  protected $dciConfig = [
    'DCI_ComposerInstall=true',
    'DCI_CoreBranch=8.3.x',
    'DCI_CoreRepository=file:///tmp/drupal',
    'DCI_GitCommitHash=c187f1d',
    'DCI_JobType=simpletest',
    'DCI_JunitXml=xml',
    'DCI_TestGroups=Url',
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
    $display = $app_tester->getDisplay();
    $this->assertNotRegExp('/.*simpletestlegacy7*/', $app_tester->getDisplay());
    $this->assertRegExp('/.*Drupal\\\\KernelTests\\\\Core\\\\Routing\\\\UrlIntegrationTest*/', $app_tester->getDisplay());
    // Look for junit xml results file
    $output_file = $job->getJobCodebase()
        ->getWorkingDir() . "/artifacts/" . $job->getBuildVars()["DCI_JunitXml"] . '/testresults.xml';
    $this->assertFileExists($output_file);
    // create a test fixture that contains the xml output results.
    //$this->assertFileEquals();
    $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/CoreD8PassingTest_testresults.xml', $output_file);
  }
}