<?php

/**
 * @file
 * Command class for pull.
 */

namespace DrupalCI\Console\Command;

use DrupalCI\Console\Command\DrupalCICommandBase;
use DrupalCI\Console\Helpers\ContainerHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Docker\Context\Context;
use DrupalCI\Console\Output;
use Symfony\Component\Console\Helper\ProgressBar;


class PullCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('pull')
      ->setDescription('Pull DrupalCI container image from hub.docker.com.')
      ->addArgument('container_name', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Docker container image(s) to pull.')
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    Output::setOutput($output);
    $output->writeln("<info>Executing pull ...</info>");
    $images = $input->getArgument('container_name');
    // TODO: Validate passed arguments
    foreach ($images as $image) {
        $name = explode (':',$image);
        $container = $name[0];
        // check if we have a tag in the input
        if(!empty($name[1])) {
          $tag = $name[1];
        }
        else
        {
          $tag = 'latest';
        }
        Output::writeln("<comment>Pulling <options=bold>$container:$tag</options=bold> container</comment>");
        $this->pull($container ,$tag , $input);
    }
  }

  /**
   * (#inheritdoc)
   */
  protected function pull($name, $tag, InputInterface $input) {
    $manager = $this->getManager();
    $progressInformation = array();
    $response = $manager->create('', ['fromImage' => $name, 'tag' => $tag]);
    // TODO: The Create method returns chunk-encoded json. Rather than trying
    // to parse this, it was easier to just remove the progress bar for now.
    Output::writeln("<info>Pull Complete.</info>");
  }
}
