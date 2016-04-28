<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a patch is applied to core.
 *
 * This test comes from:
 * https://dispatcher.drupalci.org/job/default/92908/
 *
 * NOTE: This test assumes you have checked out Drupal 8.1.x branch into a
 * directory called /tmp/drupal.git, using a command like this:
 * git clone --bare https://git.drupal.org/project/drupal.git
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CoreNoGroupTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_ComposerInstall=true',
    'DCI_CoreBranch=8.1.x',
    'DCI_CoreRepository=file:///tmp/drupal.git',
    'DCI_DBVersion=mysql-5.5',
    'DCI_Fetch=https://www.drupal.org/files/issues/2675066-12.patch,.',
    'DCI_GitCommitHash=04038f4',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=5.5',
    'DCI_Patch=2675066-12.patch,.',
    'DCI_RunScript=/var/www/html/core/scripts/run-tests.sh',
  ];

  public function testCoreNoGroup() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/.*Error.*/', $app_tester->getDisplay());
    $this->assertRegExp('/.*Return status: 2*/', $app_tester->getDisplay());
  }
}
