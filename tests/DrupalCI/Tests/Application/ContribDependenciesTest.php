<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a D8.1.x Contrib module has dependencies.
 * https://dispatcher.drupalci.org/job/default/63496/
 *
 * NOTE: This test assumes you have checked out Drupal 8.1.x branch into a
 * directory called /tmp/drupal.git, using a command like this:
 * git clone --bare https://git.drupal.org/project/drupal.git
 *
 * @group Application
 *
 * @see TESTING.md
 */
class ContribDependenciesTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/token.git,8.x-1.x,modules/token,1;',
    'DCI_ComposerInstall=true',
    'DCI_CoreRepository=file:///tmp/drupal.git',
    'DCI_CoreBranch=8.1.x',
    'DCI_DBVersion=mysql-5.5',
    'DCI_GitCommitHash=f7b30ad',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=5.5',
    'DCI_TestItem=directory:modules/token',
  ];

  public function testContribDependencies() {
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
