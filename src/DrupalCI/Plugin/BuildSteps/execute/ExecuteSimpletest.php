<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\setup\Checkout
 *
 * Processes "setup: checkout:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\BuildSteps\execute;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use Pimple\Container;


/**
 * @PluginID("simpletest")
 */
class ExecuteSimpletest extends PluginBase implements BuildTaskInterface, Injectable {

  use BuildTaskTrait;

  /**
   * BuildSteps plugin manager.
   *
   * We'll use this to create a ContainerTestingCommand.
   *
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildStepPluginManager;

  public function getDefaultConfiguration() {
    return [
      'DCI_DBurl' => '',
      'DCI_RTColor' => TRUE,
      'DCI_RTConcurrency' => 4,
      'DCI_RTDieOnFail' => TRUE,
      'DCI_RTKeepResultsTable' => FALSE,
      'DCI_SQLite' => '/var/www/html/results/simpletest.sqlite',
      'DCI_RTTypes' => '',
      'DCI_RTUrl' => 'http://localhost/checkout',
      'DCI_RTVerbose' => TRUE,
      'DCI_RTXmlPath' => '',
      'DCI_RunScript' => '/var/www/html/core/scripts/run-tests.sh',
      'DCI_TestGroups' => '--all',
    ];
  }

  public function setContainer(Container $container) {
    $this->buildStepPluginManager = $container['plugin.manager.factory']->create('BuildSteps');
  }

  /**
   * {@inheritdoc}
   */
  public function run(BuildInterface $job, &$data) {
    $data = $this->resolveDciVariables($data);
    $data['runtests']['sqlite'] = $job->getBuildVar('DCI_Sqlite');

    $command = [$data['runtests']['testcommand']];
    $command[] = $this->getRunTestsFlagValues($data['runtests']);
    $command[] = $this->getRunTestsValues($data['runtests']);
    $command[] = $data['runtests']['testgroups'];

    $command_line = implode(' ', $command);
//    throw new \Exception('simpletest data: ' . print_r($data, true) . ' command line: ' . $command_line);

    $runner = $this->buildStepPluginManager->getPlugin('generic', 'testcommand', [$command_line]);
    $runner->run($job, $command_line);

  }

  protected function getRunTestsFlagValues($data) {
    $command = [];
    $flags = [
      'color',
      'die-on-fail',
      'keep-results-table',
      'verbose',
    ];
    foreach($data as $key => $value) {
      if (in_array($key, $flags)) {
        if ($value) {
          $command[] = "--$key";
        }
      }
    }
    return implode(' ', $command);
  }

  protected function getRunTestsValues($data) {
    $command = [];
    $args = [
      'concurrency',
      'dburl',
      'sqlite',
      'types',
      'url',
      'xml',
    ];
    foreach ($data as $key => $value) {
      if (in_array($key, $args)) {
        if ($value) {
          $command[] = "--$key $value";
        }
      }
    }
    return implode(' ', $command);
  }

}
