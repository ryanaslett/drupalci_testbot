<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;


use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\ContainerCommand;
use DrupalCI\Build\Environment\ContainerTestingCommand;
use DrupalCI\Console\Output;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskTrait;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("simpletest_d7")
 */
class SimpletestD7 extends Simpletest {

  public function setupSimpletestDB(BuildInterface $build) {

    $this->results_database = $this->system_database;
    $dburl = $this->system_database->getUrl();

    $setup_commands = [
      'cd /var/www/html && sudo -u www-data DRUSH_NO_MIN_PHP=1 /.composer/vendor/drush/drush/drush -r /var/www/html si -y --db-url=' . $dburl . ' --clean-url=0 --account-name=admin --account-pass=drupal --account-mail=admin@example.com',
      'cd /var/www/html && sudo -u www-data DRUSH_NO_MIN_PHP=1 /.composer/vendor/drush/drush/drush -r /var/www/html vset simpletest_clear_results \'0\' 2>&1',
      'cd /var/www/html && sudo -u www-data DRUSH_NO_MIN_PHP=1 /.composer/vendor/drush/drush/drush -r /var/www/html vset simpletest_verbose \'0\' 2>&1',
      'cd /var/www/html && sudo -u www-data DRUSH_NO_MIN_PHP=1 /.composer/vendor/drush/drush/drush -r /var/www/html en -y simpletest 2>&1',
      # Patch core so we can use --directory in run-tests.sh. Only necessary for
      # commits before 2551981 was added, but it will just fail to apply with no
      # effect for newer versions of core.
      'cd /var/www/html && sudo -u www-data wget -O /var/www/html/2551981-21-add-directory-option-to-run-tests.patch https://www.drupal.org/files/issues/2551981-21-add-directory-option-to-run-tests.patch',
      'cd /var/www/html && git apply ./2551981-21-add-directory-option-to-run-tests.patch || true',
    ];
    $command = new ContainerCommand();
    $command->run($build, $setup_commands);
  }


  /**
   * Turn run-test.sh flag values into their command-line equivalents.
   *
   * @param type $config
   *   This plugin's config, from run().
   *
   * @return string
   *   The assembled command line fragment.
   */
  protected function getRunTestsFlagValues($config) {
    $command = [];
    $flags = [
      'color',
      'die-on-fail',
      'verbose',
    ];
    foreach($config as $key => $value) {
      if (in_array($key, $flags)) {
        if ($value) {
          $command[] = "--$key";
        }
      }
    }
    return implode(' ', $command);
  }

  /**
   * Turn run-test.sh values into their command-line equivalents.
   *
   * @param type $config
   *   This plugin's config, from run().
   *
   * @return string
   *   The assembled command line fragment.
   */
  protected function getRunTestsValues($config) {
    $command = [];
    $args = [
      'concurrency',
      'url',
      'php',
    ];
    foreach ($config as $key => $value) {
      if (in_array($key, $args)) {
        if ($value) {
          $command[] = "--$key \"$value\"";
        }
      }
    }
    return implode(' ', $command);
  }

  /**
   * @param $test_list
   *
   * @return array
   */
  protected function parseGroups($test_list): array {
    // Set an initial default group, in case leading tests are found with no group.
    $group = 'nogroup';
    $test_groups = [];

    foreach ($test_list as $output_line) {
      if (substr($output_line, 0, 3) == ' - ') {
        // This is a class
        $lineparts = explode(' ', $output_line);
        $class = str_replace(['(', ')'], '', end($lineparts));
        $test_groups[$class] = $group;
      }
      else {
        // This is a group
        $group = ucwords($output_line);
      }
    }
    return $test_groups;
  }

}
