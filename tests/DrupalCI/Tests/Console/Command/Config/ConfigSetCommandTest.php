<?php

namespace DrupalCI\Tests\Console\Command\Config;

use DrupalCI\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Tester\CommandTester;

class ConfigSetCommandTest extends CommandTestBase {

  public function testSet() {
    $c = $this->getConsoleApp();
    $command = $c->find('config:set');
    $commandTester = new CommandTester($command);
    $commandTester->execute([
      'command' => $command->getName(),
      'assignment' => ['foof'],
    ]);
    $display = $commandTester->getDisplay(TRUE);
    $this->assertRegExp('/Unable to parse argument./', $display);
  }

}
