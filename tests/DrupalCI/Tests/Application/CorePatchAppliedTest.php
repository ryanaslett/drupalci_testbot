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
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
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
    'DCI_ComposerInstall=true',
    'DCI_CoreBranch=8.1.x',
    'DCI_CoreRepository=file:///var/lib/drupalci/drupal-checkout',
    'DCI_DBType=mysql',
    'DCI_DBVersion=5.5',
    'DCI_Fetch=https://www.drupal.org/files/issues/Generic.PHP_.UpperCaseConstant-2572307-24.patch,.',
    'DCI_GitCommitHash=bdb434a',
    'DCI_JobType=simpletest',
    'DCI_PHPVersion=5.5',
    'DCI_Patch=Generic.PHP_.UpperCaseConstant-2572307-24.patch,.',
//    'DCI_RunScript=/var/www/html/core/scripts/run-tests.sh',
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
    $this->assertEquals(0, $app_tester->getStatusCode());
  }
}
