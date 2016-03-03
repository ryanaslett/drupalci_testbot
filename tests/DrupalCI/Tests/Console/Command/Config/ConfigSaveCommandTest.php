<?php

namespace DrupalCI\Tests\Console\Command\Config;

use DrupalCI\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Tester\CommandTester;

class ConfigSaveCommandTest extends CommandTestBase {

  public function testSave() {
    $c = $this->getConsoleApp();
    $command = $c->find('config:save');
    $commandTester = new CommandTester($command);
    $commandTester->execute([
      'command' => $command->getName(),
      'configset_name' => 'foof',
    ]);
    $display = $commandTester->getDisplay(TRUE);
    $this->assertRegExp("`Unable to save an empty configuration set.`", $display);
    $this->assertRegExp("/Use the 'drupalci config:set \[variablename]=\[value]' command to set some configuration defaults before attempting to save a new config set\./", $display);
  }

}
