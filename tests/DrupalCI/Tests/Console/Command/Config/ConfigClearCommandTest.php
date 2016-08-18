<?php

namespace DrupalCI\Tests\Console\Command\Config;

use DrupalCI\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ConfigClearCommandTest extends CommandTestBase {

  public function testClear() {
    $c = $this->getConsoleApp();
    $command = $c->find('config:clear');
    $commandTester = new CommandTester($command);

    $helper = $command->getHelper('question');
    $helper->setInputStream($this->getInputStream('yes\\n'));

    $commandTester->execute([
      'command' => $command->getName(),
      'variable' => ['foof'],
    ]);
    $display = $commandTester->getDisplay(TRUE);
    $this->assertRegExp('`(These variables do not exist. No action taken on them: foof)`', $display);
  }

}
