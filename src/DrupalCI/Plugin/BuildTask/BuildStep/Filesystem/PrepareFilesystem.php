<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Filesystem;


use DrupalCI\Build\Environment\Environment;
use DrupalCI\Console\Output;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\BuildTask\FileHandlerTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use GuzzleHttp\Client;
use Pimple\Container;

/**
 * This does all the typical setup for a build. We'll probably want to move
 * some of this to other places, but it can go here during this sweep of
 * reorganization.
 *
 * @PluginID("prepare_filesystem")
 */
class PrepareFilesystem extends PluginBase implements BuildStepInterface, BuildTaskInterface, Injectable  {

  use BuildTaskTrait;
  use FileHandlerTrait;

  /* @var \DrupalCI\Build\Environment\DatabaseInterface */
  protected $system_database;

  /* @var  \DrupalCI\Build\Environment\EnvironmentInterface */
  protected $environment;

  public function inject(Container $container) {
    parent::inject($container);
    $this->system_database = $container['db.system'];
    $this->environment = $container['environment'];

  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // @TODO make into a test
     // $_ENV['DCI_Fetch']='https://www.drupal.org/files/issues/2796581-region-136.patch,.;https://www.drupal.org/files/issues/another.patch,.';
    if (isset($_ENV['DCI_Fetch'])) {
      $this->configuration['files'] = $this->process($_ENV['DCI_Fetch']);
    }
  }

  /**
   * @inheritDoc
   */
  public function run() {
   $setup_commands = [
      'mkdir -p /var/www/html/artifacts',
      'mkdir -p /var/www/html/sites/simpletest/xml',
      'ln -s /var/www/html /var/www/html/checkout',
      'chown -fR www-data:www-data /var/www/html/sites',
      'chmod 0777 /var/www/html/artifacts',
      'chmod 0777 /tmp',
      'supervisorctl start phantomjs',
      'php -v',
      # TODO: figure out what to do with this.
      'sudo bash -c "/opt/phpenv/shims/pecl list | grep -q yaml && cd /opt/phpenv/versions/ && ls | xargs -I {} -i bash -c \'echo extension=yaml.so > ./{}/etc/conf.d/yaml.ini\' || echo -n"',
    ];
    $this->environment->executeCommands($setup_commands);

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
    // TODO: Implement getDefaultConfiguration() method.

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
