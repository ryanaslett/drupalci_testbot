<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Basic test that proves that drupalci can execute a simpletest and generate a result
 *
 * NOTE: This test assumes you have checked out Drupal 7.x branch into a
 * directory called /tmp/drupal.git, using a command like this:
 * git clone --bare https://git.drupal.org/project/drupal.git
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
    'DCI_RunScript=/var/www/html/scripts/run-tests.sh',
    'DCI_TestGroups=Blog',
  ];

  private $dciConfigPhpVer = [
    'DCI_PHPVersion=5.3',
    'DCI_PHPVersion=5.4',
  ];
  private $dciConfigDb = [
    'DCI_DBVersion=mysql-5.5',
  ];

  public function testBasicTest() {
    foreach ($this->dciConfigDb as $dbKey) {
      array_push($this->dciConfig, $dbKey);
      foreach ($this->dciConfigPhpVer as $phpKey) {
        array_push($this->dciConfig, $phpKey);
        $this->setUp();
        $app = $this->getConsoleApp();
        $options = ['interactive' => FALSE];
        $app_tester = new ApplicationTester($app);
        $app_tester->run([
          'command' => 'run',
        ], $options);
        $display = $app_tester->getDisplay();
        $job = $this->getCommand('run')->getJob();
        $this->assertRegExp('/.*simpletestlegacy7*/', $app_tester->getDisplay());
        $this->assertRegExp('/.*Blog functionality 244 passes, 0 fails, and 0 exceptions*/', $app_tester->getDisplay());
        // Look for junit xml results file
        $output_file = $job->getJobCodebase()->getWorkingDir() . "/artifacts/" . $job->getBuildVars()["DCI_JunitXml"] . '/testresults.xml';
        $this->assertFileExists($output_file);
        // create a test fixture that contains the xml output results.
        //$this->assertFileEquals();
        $this->assertXmlFileEqualsXmlFile(__DIR__ . '/Fixtures/PassingSimpletestLegacy7Test_testresults.xml', $output_file);
        array_pop($this->dciConfig);
      }
      array_pop($this->dciConfig);
    }
  }
}
