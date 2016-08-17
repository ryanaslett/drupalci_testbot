<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a patch is applied to core.
 *
 * This test comes from:
 * https://dispatcher.drupalci.org/job/default/122151/consoleFull
 *
 * NOTE: This test assumes you have checked out Drupal 8.1.x branch into a
 * directory called /tmp/drupal.git, using a command like this:
 * git clone --bare https://git.drupal.org/project/drupal.git
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CorePatchAppliedTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_ComposerInstall=false',
    'DCI_CoreBranch=8.1.x',
    'DCI_CoreRepository=file:///tmp/drupal',
    //'DCI_UseLocalCodebase=/tmp/drupal',
    'DCI_DBVersion=mysql-5.5',
    'DCI_Fetch=https://www.drupal.org/files/issues/Generic.PHP_.UpperCaseConstant-2572307-24.patch,.',
    'DCI_GitCommitHash=bdb434a',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=7',
    'DCI_Patch=Generic.PHP_.UpperCaseConstant-2572307-24.patch,.',
    'DCI_RunScript=/var/www/html/core/scripts/run-tests.sh',
    'DCI_TestGroups=Url',
  ];

  public function testCorePatchApplied() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);
    $this->assertRegExp('/.*Generic.PHP_.UpperCaseConstant-2572307-24.patch applied.*/', $app_tester->getDisplay());
    $this->assertRegExp('/.*Drupal\\\\system\\\\Tests\\\\Routing\\\\UrlIntegrationTest*/', $app_tester->getDisplay());
    fwrite(STDERR, print_r($app_tester->getDisplay(), TRUE));
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
