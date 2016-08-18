<?php
/**
 * @file
 * Command class for run.
 */

namespace DrupalCI\Console\Command\Config;

use DrupalCI\Console\Command\DrupalCICommandBase;
use DrupalCI\Console\DrupalCIStyle;
use DrupalCI\Console\Helpers\ConfigHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 *   clear               Used to remove a configuration variable from the
 *                       current configuration set.
 */
class ConfigClearCommand extends DrupalCICommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('config:clear')
      ->setDescription('Reset/remove config variables.')
      ->addArgument('variable', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'List of variable names to remove from the config.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $variables = $input->getArgument('variable');
    if (empty($variables)) {
      return;
    }
    $helper = new ConfigHelper();
    foreach($variables as $variable) {
        $helper->clearConfigVariable($variable);
    }
    $deleted = implode(', ', $variables);
    $io = new DrupalCIStyle($input, $output);
    $io->success("Variables deleted from the current config set: $deleted");
  }

  /**
   * {@inheritdoc}
   */
  public function interact(InputInterface $input, OutputInterface $output) {
    $variables = $input->getArgument('variable');
    $helper = new ConfigHelper();
    $config = $helper->getCurrentConfigSetParsed();

    $missing_variables = [];
    $remove_variables = [];

    foreach ($variables as $variable) {
      // Check that the variable exists
      if (!array_key_exists($variable, $config)) {
        $missing_variables[] = $variable;
      }
      else {
        $remove_variables[] = $variable;
      }
    }

    $io = new DrupalCIStyle($input, $output);

    if (!empty($missing_variables)) {
      $variables = array_diff($variables, $missing_variables);
      $some_variables = implode(', ', $missing_variables);
      $io->note("These variables do not exist. No action taken on them: $some_variables");
    }
    if (!empty($remove_variables)) {
      $some_variables = implode(', ', $remove_variables);
      if (!$io->confirm("Are you sure you wish to remove these variables? $some_variables", TRUE)) {
        $variables = [];
      }
    }
    // Set our validated list back to the input argument.
    $input->setArgument('variable', $variables);
  }

}
