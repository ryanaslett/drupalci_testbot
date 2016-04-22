<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens a contrib module has no tests.
 *
 * This test depends on the drupalci_d8_no_tests module which you can find here:
 * https://www.drupal.org/sandbox/mile23/2683655
 *
 * NOTE: This test assumes you have checked out Drupal 8.1.x branch into a
 * directory called /tmp/drupal.git, using a command like this:
 * git clone --bare https://git.drupal.org/project/drupal.git
 *
 * @group Application
 *
 * @see TESTING.md
 */
class simpletestPatchFailTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_CoreRepository=file:///tmp/drupal.git',
    'DCI_RunScript=/var/www/html/core/scripts/run-tests.sh ',
    'DCI_PHPVersion=5.5',
    'DCI_JobType=simpletest',
    'DCI_ComposerInstall=True',
    'DCI_CoreBranch=8.1.x',
    'DCI_Patch=2684095-2.patch,.',
    'DCI_Color=True',
    'DCI_JunitXml=xml',
    'DCI_Fetch=https://www.drupal.org/files/issues/2684095-2.patch,.',
    'DCI_Concurrency=2',
  ];

  public function testSimpletestPatchFail() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/Patch Error/', $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
