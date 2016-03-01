<?php

namespace DrupalCI\Tests\Console\Command;

use Symfony\Component\Console\Tester\CommandTester;
use DrupalCI\Tests\Console\Command\CommandTestBase;

class StatusCommandTest extends CommandTestBase {

  public function testStatus() {
    $c = $this->getConsoleApp();
    $command = $c->find('status');
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command' => $command->getName()]);

    $this->assertRegExp('/Running Status Checks ... \nChecking Docker Version ... \n/', $commandTester->getDisplay(TRUE));
  }

}
