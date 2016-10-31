<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\StartContainers;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Build\Environment\DatabaseInterface;
use DrupalCI\Console\Output;
use Http\Client\Common\Exception\ClientErrorException;
use Pimple\Container;

/**
 * @PluginID("runcontainers")
 */
class RunContainers extends PluginBase implements BuildStepInterface, BuildTaskInterface, Injectable  {

  use BuildTaskTrait;

  /* @var DatabaseInterface */
  protected $database;

  /**
   * The current build.
   *
   * @var \DrupalCI\Build\BuildInterface
   */
  protected $build;

  public function inject(Container $container) {
    parent::inject($container);
    $this->database = $container['db.system'];
    $this->build = $container['build'];
  }

  /**
   * @inheritDoc
   */
  public function configure() {

    if (isset($_ENV['DCI_PHPVersion'])) {
      $this->configuration['phpversion'] = $_ENV['DCI_PHPVersion'];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function run() {

    $this->io->writeln("<info>Parsing required Web container image names ...</info>");
    $containers = $this->build->getExecContainers();
    $containers['web'] = $this->buildWebImageNames($this->configuration['phpversion']);
    $valid = $this->validateImageNames($containers['web'], $this->build);
    if (!empty($valid)) {
      $this->build->setExecContainers($containers);
      // Actual creation and configuration of the executable containers occurs
      // during the 'getExecContainers()' method call.
    }

    // We don't need to initialize any service container for SQLite.
    if (strpos($this->database->getDbType(), 'sqlite') === 0) {
      return;
    }
    $this->io->writeln("<info>Parsing required database container image names ...</info>");
    $containers = $this->buildImageNames();
    if ($valid = $this->validateImageNames($containers, $this->build)) {
      // @todo Move the housekeeping to the build instead of doing it here.
      $service_containers = $this->build->getServiceContainers();
      $service_containers['db'] = $containers;
      $this->build->setServiceContainers($service_containers);
      $this->build->startServiceContainerDaemons('db');
    }
  }

  public function buildImageNames() {
    $db_version = $this->database->getDbType() . '-' . $this->database->getVersion();
    $images["$db_version"]['image'] = "drupalci/$db_version";
    $this->io->writeln("<comment>Adding image: <options=bold>drupalci/$db_version</options=bold></comment>");
    return $images;
  }

  protected function buildWebImageNames($php_version) {
    $images["web-$php_version"]['image'] = "drupalci/web-$php_version";
    $this->io->writeln("<comment>Adding image: <options=bold>drupalci/web-$php_version</options=bold></comment>");
    return $images;
  }

  public function validateImageNames($containers, BuildInterface $build) {
    // Verify that the appropriate container images exist
    $this->io->writeln("<comment>Validating container images exist</comment>");
    // DOCKER
    $docker = $build->getDocker();
    $manager = $docker->getImageManager();
    foreach ($containers as $key => $image_name) {
      $container_string = explode(':', $image_name['image']);
      $name = $container_string[0];

      try {
        $image = $manager->find($name);
      }
      catch (ClientErrorException $e) {
        $this->io->drupalCIError("Missing Image", "Required container image <options=bold>'$name'</options=bold> not found.");
        return FALSE;
      }
      $id = substr($image->getID (), 0, 8);
      $this->io->writeln("<comment>Found image <options=bold>$name/options=bold> with ID <options=bold>$id</options=bold></comment>");
    }
    return TRUE;
  }


  /**
   * @inheritDoc
   */
  public function complete() {
    // TODO: Implement complete() method.
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'phpversion' => '5.5',
    ];
  }

  /**
   * @inheritDoc
   */
  public function getChildTasks() {
    // TODO: Implement getChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function setChildTasks($buildTasks) {
    // TODO: Implement setChildTasks() method.
  }

  /**
   * @inheritDoc
   */
  public function getShortError() {
    // TODO: Implement getShortError() method.
  }

  /**
   * @inheritDoc
   */
  public function getErrorDetails() {
    // TODO: Implement getErrorDetails() method.
  }

  /**
   * @inheritDoc
   */
  public function getResultCode() {
    // TODO: Implement getResultCode() method.
  }

  /**
   * @inheritDoc
   */
  public function getArtifacts() {
    // TODO: Implement getArtifacts() method.
  }



}
