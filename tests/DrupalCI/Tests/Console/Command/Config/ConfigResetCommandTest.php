<?php

namespace DrupalCI\Tests\Console\Command\Config;

use DrupalCI\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Tester\CommandTester;

class ConfigResetCommandTest extends CommandTestBase {

  public function testReset() {
    $c = $this->getConsoleApp();
    $command = $c->find('config:reset');
    $commandTester = new CommandTester($command);
    $commandTester->execute([
      'command' => $command->getName(),
      'setting' => ['foof'],
    ]);
    $display = $commandTester->getDisplay(TRUE);
    $this->assertRegExp("`The 'foof' configuration set does not exist.`", $display);
  }

}
