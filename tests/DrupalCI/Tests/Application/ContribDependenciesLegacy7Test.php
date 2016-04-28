<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a D7 Contrib module has dependencies.
 *
 *
 * NOTE: This test assumes you have checked out Drupal 8.1.x branch into a
 * directory called /tmp/drupal.git, using a command like this:
 * git clone --bare https://git.drupal.org/project/drupal.git
 *
 * @group Application
 *
 * @see TESTING.md
 */
class ContribDependenciesLegacy7Test extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/forena.git,7.x-4.x,sites/all/modules/forena,1;',
    'DCI_ComposerInstall=false',
    'DCI_CoreBranch=7.x',
    'DCI_CoreRepository=file:///tmp/drupal.git',
    'DCI_DBVersion=mysql-5.5',
    'DCI_GitCommitHash=d33ac7e',
    'DCI_JobType=simpletestlegacy7',
    'DCI_PHPVersion=5.3',
    'DCI_RunScript=/var/www/html/scripts/run-tests.sh',
    'DCI_TestItem=directory:sites/all/modules/forena',
  ];

  public function testContribDependenciesLegacy7() {
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
