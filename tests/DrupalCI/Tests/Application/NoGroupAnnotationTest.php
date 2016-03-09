<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when run-test.sh runs a test with no @group annotation.
 *
 * NOTE: This test assumes you have checked out Drupal 8.1.x branch into a
 * directory called /tmp/drupal, using a command like this:
 * git clone --branch 8.1.x https://git.drupal.org/project/drupal.git
 *
 * @group Application
 *
 * @see TESTING.md
 */
class NoGroupAnnotationTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_UseLocalCodebase=/tmp/drupal',
    'DCI_JobType=simpletest',
    'DCI_CoreBranch=8.1.x',
    'DCI_TestGroups=Core',
    'DCI_Fetch=https://www.drupal.org/files/issues/2680713-test-with-no-group-annotation.patch',
    'DCI_Patch=2680713-test-with-no-group-annotation.patch',
  ];

  public function testContribNoTests() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];

    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);

    $display = $app_tester->getDisplay(TRUE);
    // Check that the patch was applied.
    $this->assertRegExp('`Fetch of https:\/\/www\.drupal\.org\/files\/issues\/2680713-test-with-no-group-annotation\.patch to .*2680713-test-with-no-group-annotation\.patch complete\.`', $display);
    $this->assertRegExp('`Patch .*2680713-test-with-no-group-annotation\.patch applied to directory`', $display);

    // See if the exception message bubbled up.
    $this->assertRegExp('`Missing @group annotation in Drupal\\Tests\\Core\\NoGroupTest`', $display);

    // Missing @group should not result in a 0 status code.
    $this->assertNotEquals(0, $app_tester->getStatusCode());
  }

}
