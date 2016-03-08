<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\Application\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when a patch fails to apply properly.
 *
 * NOTE: This test assumes you have checked out Drupal 8.1.x branch into a
 * directory called /tmp/drupal, using a command like this:
 * git clone --branch 8.1.x https://git.drupal.org/project/drupal.git
 */
class PatchFailTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_UseLocalCodebase=/tmp/drupal',
    'DCI_JobType=simpletest',
    'DCI_CoreBranch=8.1.x',
    'DCI_TestGroups=ban',
    'DCI_Fetch=http://drupal.org/files/issues/add_new_file.patch',
    'DCI_Patch=add_new_file.patch',
  ];

  public function testBadPatch() {
    $app = $this->getConsoleApp();
    $app->setAutoExit(TRUE);
    $options = ['interactive' => FALSE];

    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);

    $this->assertNotEquals(0, $app_tester->getStatusCode());
  }

}
