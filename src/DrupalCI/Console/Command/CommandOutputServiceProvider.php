<?php

namespace DrupalCI\Console\Command;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandOutputServiceProvider implements ServiceProviderInterface {

  /**
   * The output object to inject.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  public function __construct(OutputInterface $output) {
    $this->output = $output;
  }

  public function register(Container $pimple) {
    $pimple['command.output'] = $this->output;
  }
}
