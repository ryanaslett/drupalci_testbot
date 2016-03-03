<?php

namespace DrupalCI\Tests\Console\Command\Config;

use DrupalCI\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ConfigLoadCommandTest extends CommandTestBase {

  public function testLoad() {
    $c = $this->getConsoleApp();
    $command = $c->find('config:load');
    $commandTester = new CommandTester($command);
    $commandTester->execute([
      'command' => $command->getName(),
      'configset' => 'foof',
    ]);
    $display = $commandTester->getDisplay(TRUE);
    $this->assertRegExp('`Unable to load configset. The specified configset does not exist.`', $display);
  }

}
