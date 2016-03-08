<?php

namespace DrupalCI\Tests\Console\Command;

use DrupalCI\Console\Helpers\ConfigHelper;
use DrupalCI\Tests\Console\Command\CommandTestBase;
use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends DrupalCIFunctionalTestBase {

  public function testRun() {
    // Load the blank configset.
    // @todo: Convert this test to DrupalCIFunctionalTestBase after
    //   https://www.drupal.org/node/2683013
    $config_helper = new ConfigHelper();
    if (!$config_helper->activateConfig('blank')) {
      throw new \PHPUnit_Framework_Exception('Unable to load blank configset.');
    }

    $c = $this->getConsoleApp();
    $command = $c->find('run');
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command' => $command->getName()]);
    $display = $commandTester->getDisplay(TRUE);
    $this->assertRegExp('`Executing job with build ID:`', $display);
    $this->assertRegExp('`Loading DrupalCI platform default arguments:`', $display);
  }
}
