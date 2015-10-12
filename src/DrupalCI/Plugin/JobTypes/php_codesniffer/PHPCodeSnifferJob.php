<?php

/**
 * @file
 * Job class for PHP CodeSniffer jobs on DrupalCI.
 */

namespace DrupalCI\Plugin\JobTypes\php_codesniffer;

use DrupalCI\Plugin\JobTypes\JobBase;

/**
 * Job for performing a coding standards check using PHP CodeSniffer.
 *
 * This is using the Coder module which contains a ruleset for the Drupal coding
 * standard.
 *
 * @todo Rework this to support any PHP CodeSniffer coding standard.
 *
 * @PluginID("php_codesniffer")
 */
class PHPCodeSnifferJob extends JobBase {

  /**
   * {@inheritdoc}
   */
  public $jobType = 'php_codesniffer';

  /**
   * {@inheritdoc}
   */
  public $defaultArguments = array(
    'DCI_PHPVersion' => '5.5',
    'DCI_CoreRepository' => 'git://drupalcode.org/project/drupal.git',
    'DCI_CoreBranch' => '8.0.x',
    'DCI_GitCheckoutDepth' => '1',
    'DCI_CoderRepository' => 'git://drupalcode.org/project/coder.git',
    'DCI_CoderBranch' => '8.x-2.x',
    'DCI_CoderCheckoutDir' => './modules/coder',
    'DCI_RunScript' => "/var/www/html/modules/coder/vendor/bin/phpcs",
    'DCI_RunOptions' => "standard=/var/www/html/core/phpcs.xml",
    'DCI_RunTarget' => "/var/www/html/core"
  );

  /**
   * {@inheritdoc}
   */
  public $availableArguments = array(
    // Variables available for any job type.
    'DCI_UseLocalCodebase' => 'Used to define a local codebase to be cloned (instead of performing a Git checkout)',
    'DCI_WorkingDir' => 'Defines the location to be used in creating the local copy of the codebase, to be mapped into the container as a container volume.  Default: /tmp/simpletest-[random string]',
    'DCI_ResultsServer' => 'Specifies the url string of a DrupalCI results server for which to publish job results',
    'DCI_ResultsServerConfig' => 'Specifies the location of a configuration file on the test runner containg a DrupalCI Results Server configuration to use in publishing results.',
    'DCI_JobBuildId' => 'Specifies a unique build ID assigned to this job from an upstream server',
    'DCI_JobId' => 'Specifies a unique results server node ID to use when publishing results for this job.',
    'DCI_JobType' => 'Specifies a default job type to assume for a "drupalci run" command',
    'DCI_EXCLUDE' => 'Specifies whether to exclude the .git directory during a clone of a local codebase.',  //TODO: Check logic, may be reversed.

    // Default variables defined for every PHP CodeSniffer job.
    'DCI_PHPVersion' => 'Defines the PHP Version used within the executable container for this job type.  Default: 5.4',
    'DCI_CoreRepository' => 'Defines the primary repository to be checked out while building the codebase to test.  Default: git://drupalcode.org/project/drupal.git',
    'DCI_CoreBranch' => 'Defines the branch on the primary repository to be checked out while building the codebase to test.  Default: 8.0.x',
    'DCI_GitCheckoutDepth' => 'Defines the depth parameter passed to git clone while checking out the core repository.  Default: 1',
    'DCI_CoderRepository' => 'Defines the repository of the Coder module to be checked out.',
    'DCI_CoderBranch' => 'Defines the branch of the Coder module to use.',
    'DCI_CoderCheckoutDir' => 'Defines the directory in which the Coder module will be installed.',
    'DCI_RunScript' => 'Defines the default run script to be executed on the container.',
    'DCI_RunOptions' => 'A string containing initial runScript options to append to the run script when performing a job.',
    'DCI_RunTarget' => 'A string defining the initial runScript target to append to the run script when performing a job.',
  );

}
