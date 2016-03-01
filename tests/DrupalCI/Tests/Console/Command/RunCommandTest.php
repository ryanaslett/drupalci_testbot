<?php

namespace DrupalCI\Tests\Console\Command;

use DrupalCI\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class RunCommandTest extends CommandTestBase {

  public function testStatus() {
    $c = $this->getConsoleApp();
    $command = $c->find('run');
    $commandTester = new CommandTester($command);
    try {
      $commandTester->execute(['command' => $command->getName()]);
    }
    catch (FileNotFoundException $e) {
      $display = $commandTester->getDisplay(TRUE);
      $this->assertRegExp('`Executing job with build ID:`', $display);
      $this->assertRegExp('`Loading DrupalCI platform default arguments:`', $display);
      $this->assertRegExp('`Using job definition template: ./drupalci.yml`', $display);
      return;
    }
    $this->fail('Run command did not throw exception or display status info.');
  }

}
