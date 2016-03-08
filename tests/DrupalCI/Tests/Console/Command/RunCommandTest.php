<?php

namespace DrupalCI\Tests\Console\Command;

use DrupalCI\Console\Helpers\ConfigHelper;
use DrupalCI\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class RunCommandTest extends CommandTestBase {

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
