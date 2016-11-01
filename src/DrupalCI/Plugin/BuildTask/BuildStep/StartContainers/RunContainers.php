<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\StartContainers;


use Docker\DockerClient;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Build\Environment\DatabaseInterface;
use DrupalCI\Console\Output;
use DrupalCI\Providers\DockerServiceProvider;
use Http\Client\Common\Exception\ClientErrorException;
use Pimple\Container;

/**
 * @PluginID("runcontainers")
 */
class RunContainers extends PluginBase implements BuildStepInterface, BuildTaskInterface, Injectable  {

  use BuildTaskTrait;

  /* @var DatabaseInterface */
  protected $database;

  /* @var \DrupalCI\Build\Environment\EnvironmentInterface */
  public $environment;

  public function inject(Container $container) {
    parent::inject($container);
    $this->database = $container['db.system'];
    $this->environment = $container['environment'];
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
    // 2. generates a container image name from the php version -
    //  drupalci/web-<phpversion>
    $containers['web'] = $this->buildWebImageNames($this->configuration['phpversion']);

    // 3. confirms that the image name that we want to make a container out of
    // has been pulled down.
    $valid = $this->environment->validateImageNames($containers['web']);
    // 4. If we find a valid container, then we setExecContainers it.
    if (!empty($valid)) {
      $this->environment->setExecContainers($containers);
      // Actual creation and configuration of the executable containers occurs
      // during the 'getExecContainers()' method call.
    }

    // We don't need to initialize any service container for SQLite.
    if (strpos($this->database->getDbType(), 'sqlite') === 0) {
      return;
    }
    $this->io->writeln("<info>Parsing required database container image names ...</info>");
    // Generates the drupalci/<dbtype>-<dbverison> image name
    $containers['db'] = $this->buildDbImageNames();
    // confirms that the service container we want to create is valid.
    if ($valid = $this->environment->validateImageNames($containers['db'])) {
      $this->environment->setServiceContainers($containers);
      $this->environment->startServiceContainerDaemons('db');
    }
  }

  public function buildDbImageNames() {
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
