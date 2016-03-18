<?php

/**
 * @file
 * Contains \DrupalCI\Tests\DrupalCITestCase.
 */

namespace DrupalCI\Tests;

use DrupalCI\Console\Output;
use DrupalCI\Providers\ConsoleOutputServiceProvider;
use Pimple\Container;
use Symfony\Component\Console\Output\OutputInterface;

class DrupalCITestCase extends \PHPUnit_Framework_TestCase {

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $output;

  /**
   * @var \DrupalCI\Plugin\JobTypes\JobInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $job;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->output = $this->getMock(OutputInterface::class);
    Output::setOutput($this->output);
    $this->job = $this->getMock('DrupalCI\Plugin\JobTypes\JobInterface');
  }

  /**
   * Get a fixture container for use in tests.
   *
   * @param array $values
   *   (optional) Values to place in the container on initialization.
   *
   * @return \Pimple\Container
   *   The container.
   */
  protected function fixtureContainer($values = []) {
    $container = new Container($values);
    $container->register(new ConsoleOutputServiceProvider($this->output));
    return $container;
  }

}
