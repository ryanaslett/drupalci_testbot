<?php

/**
 * @file
 * Command class for docker remove.
 */

namespace DrupalCI\Console\Command\Docker;

use DrupalCI\Console\Command\Drupal\DrupalCICommandBase;
use DrupalCI\Helpers\ContainerHelper;
use DrupalCI\Console\Output;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DockerRemoveCommand extends DrupalCICommandBase {
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('docker-rm')
      ->setDescription('Docker Remove containers associated with DrupalCI.')
      ->addArgument('type', InputArgument::REQUIRED, 'Type of removing to do. One of: db, web, or containers.')
      ->addOption('list', '', InputOption::VALUE_NONE, 'List DCI containers.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $helper = new ContainerHelper();
    $containers = $helper->getAllContainers();
    $types = array(
      'containers', 'db', 'web',
    );
    $type = $input->getArgument('type');
    if (!in_array($type, $types)) {
      $this->io->writeln('<error>' . $type . ' is not a legal container type.</error>');
      return;
    }

    if ($input->getOption('list')) {
      $this->listContainers($type);
    }
    else {
      $this->removeContainers($type);
    }
  }

  /**
   * (@inheritdoc)
   */
  protected function listContainers($type) {

    $helper = new ContainerHelper();
    $containers = $helper->getAllContainers();
    foreach ($containers as $containerLabel => $containerName) {
      $this->io->writeln("<comment>$containerLabel, $containerName</comment>");
    }
  }

  /**
   * (@inheritdoc)
   */
  protected function imageTypes($type) {
    // get list DCI container type
    $image_type = '';
    switch ($type) {
    case 'containers':
      break;
    case 'db':
      $image_type = 'mysql|pgsql|mariadb';
      break;
    case 'web':
      $image_type = 'web';
      break;
    case 'php':
      $image_type = 'php';
      break;
    }
    return $image_type;
  }

  /**
   * (@inheritdoc)
   */
  protected function removeContainers($type) {

    // DCI search string
    $search_string = 'drupalci';
    $image_type = $this->imageTypes($type);
    if(!empty($image_type)){
      $search_string .= "' | egrep '" . $image_type;
    }

    // get list of create DCI containers
    $cmd_docker_psa = "docker ps -a | grep '" . $search_string . "' | awk '{print $1}'";
    // get list of active DCI containers
    $cmd_docker_ps = "docker ps | grep '" . $search_string . "' | awk '{print $1}'";
    exec($cmd_docker_psa, $createdContainers);

    if($createdContainers) {
      $this->io->writeln('<comment>Removing containers.</comment>');
      exec($cmd_docker_ps, $runningContainers);
      if(!empty($runningContainers)){
        // kill DCI running containers
        $cmd_docker_kill = "docker kill " . implode(' ', $runningContainers);
        exec( $cmd_docker_kill, $killContainers);
      }

      // remove DCI containers
      $cmd_docker_rm = "docker rm " . implode(' ', $createdContainers);
      exec( $cmd_docker_rm, $rmContainers);

      // list removed containers
      $this->io->writeln('Removed Containers:');
      $this->io->writeln($rmContainers);

      // DEBUG
      //$this->io->writeln($rmContainers);

      //check to for any DCI after the kill and remove
      exec($cmd_docker_psa, $remove_check);

      if (!empty($remove_check)) {
        $this->io->writeln('<error>Error:</error>');
        $this->io->writeln($remove_check);
      }
      else {
        $this->io->writeln('<comment>Remove complete.</comment>');
      }
    }
    else {
      // nothing to remove
      $this->io->writeln('<comment>Nothing to Remove</comment> ');
    }

  }
}
