<?php

namespace DrupalCI\Tests\Console\Command;

use Docker\Exception\ImageNotFoundException;
use DrupalCI\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Tester\CommandTester;

class PullCommandTest extends CommandTestBase {

  /**
   * @todo: This test relies on a timeout of the docker image service. Fix that.
   * @group docker
   *
   * @coversNothing
   */
  public function testPull() {
    $c = $this->getConsoleApp();
    $command = $c->find('pull');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
      'command' => $command->getName(),
      'container_name' => ['foof'],
    ]);

    $display = $commandTester->getDisplay(TRUE);
    $this->assertRegExp('`Executing pull ...`', $display);
    $this->assertRegExp('`Pulling foof container`', $display);
    return;
  }

}
