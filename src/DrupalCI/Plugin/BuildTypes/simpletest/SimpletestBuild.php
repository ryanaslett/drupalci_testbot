<?php

/**
 * @file
 * Build class for SimpleTest builds on DrupalCI.
 */

namespace DrupalCI\Plugin\BuildTypes\simpletest;

use DrupalCI\Build\Build;

/**
 * @PluginID("simpletest")
 */

class SimpletestBuild extends Build {

  /**
   * @var string
  */
  public $buildType = 'simpletest';

  /**
   * Default Arguments (defaultArguments)
   *
   * @var array
   *
   * Each DrupalCI Build type needs to contain a 'defaultArguments' property,
   * which contains a list of DCI_* variables and default values; which defines
   * the default behaviour of that build type if no additional overrides are
   * passed into an instance of that build type.
   */
  public $defaultArguments = array(
    // ** Environment Defaults
    'DCI_DBType' => 'mysql',
    'DCI_DBVersion' => '5.5',
    // ** Database Default
    'DCI_DBUser' => 'drupaltestbot',
    'DCI_DBPassword' => 'drupaltestbotpw',
    'DCI_DBUrl' => 'dbtype://host', // DBVersion, DBUser and DBPassword variable plugins will change this.
    // ** startcontainers defaults
    'DCI_PHPVersion' => '5.5',


    // Codebase
    'DCI_CoreRepository' => 'git://drupalcode.org/project/drupal.git',
    'DCI_CoreBranch' => '8.0.x',
    'DCI_GitCheckoutDepth' => '1',
    'DCI_GitCommitHash' => '',

    // Simpletest Defaults
    'DCI_RunScript' => '/var/www/html/core/scripts/run-tests.sh ',
    'DCI_TestGroups' => '--all',
    'DCI_SQLite' => '/var/www/html/artifacts/simpletest.sqlite',
    'DCI_Concurrency' => 4,
    'DCI_RunOptions' => 'color;keep-results',
    'DCI_PHPInterpreter' => '/opt/phpenv/shims/php',

    // 'DCI_XMLOutput' => '/var/www/html/results/xml',
    // Environment
    'DCI_SyntaxCheck' => TRUE,
  );

  /**
   * Required Arguments, which must be present in order for the build to attempt
   * to run.
   *
   * The expected format here is an array of key=>value pairs, where the key is
   * the name of a DCI_* environment variable, and the value is the array key
   * path from the parsed .yml file build definition that would need to be
   * traversed to get to the location that variable would exist in the build
   * definition.
   *
   * As an example, DCI_DBVersion defines the database type (mysql, pgsql, etc)
   * for a given build. In a parsed .yml build definition file, this information
   * would be stored in the value located at:
   * array(
   *   'environment' => array(
   *     'db' => VALUE
   *   )
   * );
   * Thus, thus the traversal path value stored in the 'requiredArguments'
   * array is the array keys 'environment:db'.
   *
   * As any required arguments for the simpletest build type are defined in the
   * 'defaultArguments' property, this array is empty.  However, that may not
   * always be the case for other build types.
   */
  public $requiredArguments = array(

  );

  /**
   * Return an array of possible argument variables for this build type.
   *
   * The 'availableArguments' property is intended to provide a complete list
   * of possible variable values which can affect this particular build type,
   * along with details regarding how each variable affects the build operation.
   * These are specified in an array, with the variable names used as the keys
   * for the array and the description used as the array values.
   */
  public $_availableArguments = array(
    // ***** Variables Available for any build type *****
    'DCI_UseLocalCodebase' => 'Used to define a local codebase to be cloned (instead of performing a Git checkout)',
    'DCI_WorkingDir' => 'Defines the location to be used in creating the local copy of the codebase, to be mapped into the container as a container volume.  Default: /tmp/simpletest-[random string]',
    'DCI_BuildId' => 'Specifies a unique build ID assigned to this build from an upstream server',
    'DCI_JobType' => 'Specifies a default build type to assume for a "drupalci run" command',
    'DCI_EXCLUDE' => 'Specifies whether to exclude the .git directory during a clone of a local codebase.',

     // ***** Default Variables defined for every simpletest build *****
    'DCI_DBVersion' => 'Defines the database version for this particular simpletest run. May map to a required service container. Default: mysql-5.5',
    'DCI_PHPVersion' => 'Defines the PHP Version used within the executable container for this build type.  Default: 5.5',
    'DCI_CoreRepository' => 'Defines the primary repository to be checked out while building the codebase to test.  Default: git://drupalcode.org/project/drupal.git',
    'DCI_CoreBranch' => 'Defines the branch on the primary repository to be checked out while building the codebase to test.  Default: 8.0.x',
    'DCI_GitCheckoutDepth' => 'Defines the depth parameter passed to git clone while checking out the core repository.  Default: 1',
    'DCI_RunScript' => 'Defines the default run script to be executed on the container.  Default: /var/www/html/core/scripts/run-tests.sh',
    'DCI_RunOptions' => 'A string containing a series of any other run script options to append to the run script when performing a build.',
    'DCI_DBUser' => 'Defines the default database user to be used on the site database.  Default: drupaltestbot',
    'DCI_DBPassword' => 'Defines the default database password to be used on the site database.  Default: drupaltestbotpw',
    'DCI_DBUrl' => 'Define the --dburl parameter to be passed to the run script.  Default: dbtype://host (DBVersion, DBUser and DBPassword variable plugins will populate this).',
    'DCI_TestGroups' => 'Defines the target test groups to run.  Default: --all',
    'DCI_SQLite' => 'Defines the location of the sqlite database used to store test results.  Default: /var/www/html/results/simpletest.sqlite',
    'DCI_Concurrency' => 'Defines the value to pass to the --concurrency argument of the run script.  Default: 4',
    'DCI_XMLOutput' => 'Defines the directory where xml results will be stored.  Default: output/var/www/html/results/xml',
    'DCI_PHPInterpreter' => 'Defines the php interpreter to be passed to the Run Script in the --php argument.  Default: /opt/phpenv/shims/php',
    // Default: 'color;'

    // ***** Optional arguments *****
    'DCI_DieOnFail' => 'Defines whether to include the --die-on-fail flag in the Run Script options',
    'DCI_SQLite' => 'Defines whether to include the --sqlite flag in the Run Script options',
    'DCI_Fetch' => 'Used to specify any files which should be downloaded while building out the codebase.',
    // Syntax: 'url1,relativelocaldirectory1;url2,relativelocaldirectory2;...'
    'DCI_Patch' => 'Defines any patches which should be applied while building out the codebase.',
    // Syntax: 'localfile1,applydirectory1;localfile2,applydirectory2;...'
    'DCI_ResultsDirectory' => 'Defines the local directory within the container where the xml results file should be written.',
    'DCI_RunScriptArguments' => 'An array of other build script options which will be added to the runScript command when executing a build.',
    // Syntax: 'argkey1,argvalue1;argkey2,argvalue2;argkey3;argkey4,argvalue4;'
    'DCI_GitCommitHash' => 'This allows to checkout specific core commits, useful for regression testing',
  );

  /**
   * Identify any priority variables which must be pre-processed before others
   *
   * Variables provided in this array will be prioritized and run first, before
   * any other DCI_* variable preprocessors are executed.
   */
  public $priorityArguments = array(
    // CoreProject doesn't *need* to run first, but it seems like a good idea
    'DCI_CoreProject',
    // Expand run options to their argument format, before adding arguments
    'DCI_RunOptions',
    // CoreBranch needs to be able to override the SQLite variable before the
    // SQLite option is added to RunOptions
    'DCI_CoreBranch',
  );

}
