<?php

/**
 * @file
 * Command class for pull.
 */

namespace DrupalCI\Console\Command\Docker;

use DrupalCI\Console\Command\Drupal\DrupalCICommandBase;
use DrupalCI\Helpers\ContainerHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Docker\Context\Context;
use DrupalCI\Console\Output;
use Symfony\Component\Console\Helper\ProgressBar;
use Docker\API\Model\CreateImageInfo;


class DockerPullCommand extends DrupalCICommandBase {
  // DOCKER
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
    // OPUT
    Output::setOutput($output);
    $this->io->writeln("<info>Executing pull ...</info>");
    $images = $input->getArgument('container_name');
    // TODO: Validate passed arguments
    foreach ($images as $image) {
        $name = explode (':',$image);
        $container = $name[0];
      // OPUT
        $this->io->writeln("<comment>Pulling <options=bold>$container</options=bold> container</comment>");
        $this->pull($container, $input);
    }
  }

  /**
   * (#inheritdoc)
   */
  protected function pull($name, InputInterface $input) {
    $manager = $this->getManager();
    $progressInformation = null;
    $response = $manager->create('', ['fromImage' => $name],  $manager::FETCH_STREAM);

    //$response->onFrame(function (CreateImageInfo $createImageInfo) use (&$progressInformation) {
    $response->onFrame(function (CreateImageInfo $createImageInfo) use (&$progressInformation) {
      $createImageInfoList[] = $createImageInfo;
        if ($createImageInfo->getStatus() === "Downloading") {
          $progress = $createImageInfo->getProgress();
          preg_match("/\]\s+(?P<current>(?:[0-9\.]+)?)\s[kM]*B\/(?P<total>(?:[0-9\.]+)?)\s/",$progress,$status);
          // OPUT
          $progressbar = new ProgressBar(Output::getOutput(), $status['total']);
          $progressbar->start();
          $progressbar->advance($status['current']);
        } else {
          // OPUT
          $this->io->writeln("<comment>" . $createImageInfo->getStatus() . "</comment>");
        }
    });
    $response->wait();

    // OPUT
    $this->io->writeln("");
  }
}
