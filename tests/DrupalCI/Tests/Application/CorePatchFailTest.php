<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a patch fails to apply properly.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 *
 * @see TESTING.md
 */
class CorePatchFailTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_CoreBranch=8.1.x',
    'DCI_CoreRepository=file:///var/lib/drupalci/drupal-checkout',
    'DCI_DBVersion=mysql-5.5',
    'DCI_Fetch=http://drupal.org/files/issues/does_not_apply.patch',
    'DCI_JobType=simpletest',
    'DCI_JunitXml=xml',
    'DCI_PHPVersion=7',
    'DCI_Patch=does_not_apply.patch',
    'DCI_TestGroups=ban',
  ];

  public function testBadPatch() {

    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];

    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);

    $this->assertRegExp('/.*The patch attempt returned an error.*/', $app_tester->getDisplay());
    // The testbot should not return 0 if there was an error.
    // @todo: Poke around in artifacts to verify that the testbot is telling
    //   d.o or other consumers that this is a failed test.

    // Currently the result code of the failed patch does not bubble up to the exit code of the
    // drupalci run command.
    $this->markTestIncomplete('Currently the result code of the failed patch does not bubble up to the exit code of the run command.');

    $this->assertNotEquals(0, $app_tester->getStatusCode());
  }

}
