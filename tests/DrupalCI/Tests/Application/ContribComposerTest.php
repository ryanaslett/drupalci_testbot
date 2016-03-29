<?php

namespace DrupalCI\Tests\Application;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens a contrib module has a composer.json file.
 *
 * This test depends on the drupalci_d8_module_composer module which you can
 * find here: https://www.drupal.org/sandbox/mile23/2695805
 *
 * NOTE: This test assumes you have checked out Drupal 8.1.x branch into a
 * directory called /tmp/drupal, using a command like this:
 * git clone --branch 8.1.x https://git.drupal.org/project/drupal.git
 *
 * @group Application
 *
 * @see TESTING.md
 */
class ContribComposerTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_UseLocalCodebase=/tmp/drupal',
    'DCI_CoreBranch=8.1.x',
    'DCI_AdditionalRepositories=git,https://git.drupal.org/sandbox/Mile23/2695805.git,8.x-1.x,modules/drupalci_d8_module_composer,1;',
    'DCI_DBVersion=mysql-5.5',
    'DCI_PHPVersion=5.5',
    'DCI_JobType=simpletest',
    'DCI_TestItem=directory:modules/drupalci_d8_module_composer',
    'DCI_ComposerInstall=true',
  ];

  public function testContribWithComposer() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];

    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
    ], $options);

    // run-tests.sh should output a string like this if our module's test
    // passes:
    // Drupal\Tests\drupalci_d8_module_composer\Unit\DependencyTest   1 passes
    $this->assertRegExp("/Drupal\\\\Tests\\\\drupalci_d8_module_composer\\\\Unit\\\\DependencyTest\s{3}1 passes/", $app_tester->getDisplay());
    $this->assertEquals(0, $app_tester->getStatusCode());
  }

}
