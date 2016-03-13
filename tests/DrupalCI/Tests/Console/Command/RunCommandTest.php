<?php

namespace DrupalCI\Tests\Console\Command;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_UseLocalCodebase=/tmp/drupal',
    'DCI_JobType=simpletest',
    'DCI_TestGroups=ban',
  ];

  public function testRun() {
    $c = $this->getConsoleApp();
    $command = $c->find('run');
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command' => $command->getName()]);

    $display = $commandTester->getDisplay(TRUE);

    $this->assertRegExp('`Executing job with build ID:`', $display);
    $this->assertRegExp('`Loading DrupalCI platform default arguments:`', $display);

    // Make sure environment step output is discovered.
    $this->assertRegExp('`Validating container images exist`', $display);
    $this->assertRegExp('`Found image .* with ID .*\n`', $display);

    // Verify environment:db.
    $this->assertRegExp('`Executing environment:db`', $display);
    $this->assertRegExp('`Parsing required database container image names ...`', $display);
    $this->assertRegExp('`Adding image: drupalci/mysql.*`', $display);
    $this->assertRegExp('`Attempting to connect to database server.`', $display);
    $this->assertRegExp('`Database is active.`', $display);
    $this->assertRegExp('`Completed environment:db`', $display);

    // Verify environment:web.
    $this->assertRegExp('`Executing environment:web`', $display);
    $this->assertRegExp('`Parsing required Web container image names ...`', $display);
    $this->assertRegExp('`Adding image: drupalci/web.*`', $display);
    $this->assertRegExp('`Completed environment:web`', $display);
  }

}
