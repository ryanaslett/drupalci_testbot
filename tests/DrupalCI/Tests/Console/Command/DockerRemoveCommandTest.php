<?php

namespace DrupalCI\Tests\Console\Command;

use Symfony\Component\Console\Tester\CommandTester;

class DockerRemoveCommandTest extends CommandTestBase {

  public function testStatus() {
    $c = $this->getConsoleApp();
    $command = $c->find('docker-rm');
    $commandTester = new CommandTester($command);
    $commandTester->execute([
      'command' => $command->getName(),
      'type' => 'illegal_container',
    ]);
    $display = $commandTester->getDisplay(TRUE);
    $this->assertRegExp('`illegal_container is not a legal container type.\n`', $display);
  }

}
