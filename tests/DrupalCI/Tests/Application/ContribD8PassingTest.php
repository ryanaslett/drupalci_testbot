<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a D8.1.x Contrib module has dependencies.
 * https://dispatcher.drupalci.org/job/default/63496/
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class ContribD8PassingTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/token.git,8.x-1.x,modules/token,1;',
    'DCI_ComposerInstall=true',
    'DCI_CoreRepository=file:///var/lib/drupalci/drupal-checkout',
    'DCI_CoreBranch=8.3.x',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_GitCommitHash=24343f9',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=7',
    'DCI_TestItem=directory:modules/token',
  ];

  public function testD8Contrib() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/.*Drupal\\\\Tests\\\\token.*/', $app_tester->getDisplay());
    $this->assertRegExp('/.*Drupal\\\\token\\\\Tests.*/', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
