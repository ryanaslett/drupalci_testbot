<?php

/**
 * @file
 * Contains \DrupalCI\Build\Definition\JobDefinition.
 */

namespace DrupalCI\Build\Definition;

use DrupalCI\Helpers\ConfigHelper;
use DrupalCI\Console\Output;
use DrupalCI\Injectable;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Plugin\PluginManager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Pimple\Container;

class BuildDefinition implements Injectable {

  /**
   * @var \Pimple\Container
   */
  protected $container;

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  public function inject(Container $container) {
    $this->container = $container;
    $this->dciVariables = $container['build.vars'];
    $this->preprocessPluginManager = $container['plugin.manager.factory']->create('Preprocess');
    $this->io = $container['console.io'];
  }

  // Location of our build definition template
  protected $template_file;
  protected function setTemplateFile($template_file) {  $this->template_file = $template_file; }

  /**
   * Build variables service.
   *
   * @var \DrupalCI\Build\BuildVariablesInterface
   *
   * @todo Remove the getters and setters.
   */
  protected $dciVariables;

  public function getDCIVariables() {
    return $this->dciVariables->getAll();
  }

  public function setDCIVariables($dci_variables) {
    return $this->dciVariables->setAll($dci_variables);
  }

  public function setDCIVariable($dci_variable, $value) {
    return $this->dciVariables->set($dci_variable, $value);
  }

  public function getDCIVariable($dci_variable) {
    return $this->dciVariables->get($dci_variable, NULL);
  }

  // Contains the parsed build definition
  protected $definition = array();
  public function getDefinition() {  return $this->definition;  }
  protected function setDefinition(array $build_definition) {  $this->definition = $build_definition;  }

  // Contains the array of build steps
  protected $build_steps = array();
  public function getBuildSteps() {  return $this->build_steps;  }
  protected function setBuildSteps(array $build_steps) {  $this->build_steps = $build_steps;  }

  /**
   * @var \DrupalCI\Plugin\PluginManager;
   */
  protected $preprocessPluginManager;

  public function loadTemplateFile($template_file) {

    // Store the template location
    $this->setTemplateFile($template_file);

    // Get and parse the default definition template (containing %DCI_*%
    // placeholders) into the build definition.

    // For 'generic' builds, this is either the file passed in on the
    // 'drupalci run <filename>' command; and should be fully populated (though
    // template placeholders *can* be supported) ... or a drupalci.yml file at
    // the working directory root.

    // For other 'buildtype' builds, this is the file location returned by
    // the $build->getDefaultDefinitionTemplate() method, which defaults to
    // build_templates/<buildtype>/drupalci.yml for most build types.

    if (!file_exists($template_file)) {
      //Output::writeln("Unable to locate build definition template at <options=bold>$template_file</options=bold>");
      throw new FileNotFoundException("Unable to locate build definition template at $template_file.");
    }

    // Attempt to parse the build definition template and save it to our definition variable.
    // The YAML class will throw an exception if this fails.
    $this->setDefinition($this->loadYaml($template_file));
  }

  /**
   * Compile the complete list of DCI_* variables
   */
  public function compile(BuildInterface $build) {
    // Compile our list of DCI_* variables
    $this->compileDciVariables($build);
  }

  /**
   * Populates the build definition template based on DCI_* variables and
   * build-specific arguments
   */
  public function preprocess(BuildInterface $build) {
    // Execute variable preprocessor plugin logic
    $this->executeVariablePreprocessors();
    // Execute definition preprocessor plugin logic
    $this->executeDefinitionPreprocessors();
    // Process DCI_* variable substitution into the build definition template
    $this->substituteTemplateVariables();
    // Add the build variables and build definition to our build object, for
    // compatibility.
    $build->setBuildVars(array_merge($this->getDCIVariables(), $build->getBuildVars()));
    // Split out the final array of build steps into it's own element and store
    // it for future use.
    $this->setBuildSteps($this->parseBuildSteps());
  }

  /**
   * Validate that the build contains all required elements defined in the class
   */
  public function validate(BuildInterface $build) {
    // TODO: Move this to individual tasks. Not plausible to validate a whole Build.
    return TRUE;
  }

  /**
   * Given a file, returns an array containing the parsed YAML contents from that file
   *
   * @param $source
   *   A YAML source file
   * @return array
   *   an array containing the parsed YAML contents from the source file
   * @throws ParseException
   */
  protected function loadYaml($source) {
    if ($content = file_get_contents($source)) {
      return Yaml::parse($content);
    }
    throw new ParseException("Unable to parse empty build definition template file at $source.");
  }

  /**
   * Compiles the list of available DCI_* variables to consider with this build
   */
  protected function compileDciVariables(BuildInterface $build) {
    // Get and parse external (i.e. anything not from the default definition
    // file) build argument parameters.  DrupalCI builds are controlled via a
    // hierarchy of configuration settings, which define the behaviour of the
    // platform while running DrupalCI builds.  This hierarchy is defined as
    // follows, which each level overriding the previous:

    // 1. Out-of-the-box DrupalCI platform defaults, as defined in DrupalCI/Build/BuildBase->platformDefaults
    $platform_defaults = $build->getPlatformDefaults();
    $this->dciVariables->add($platform_defaults, 'default');
    if (!empty($platform_defaults)) {
      // OPUT
      $this->io->writeLn("<comment>Loading DrupalCI platform default arguments:</comment>");
      $this->io->writeLn(implode(",", array_keys($platform_defaults)));
    }

    // 2. Out-of-the-box DrupalCI BuildType defaults, as defined in DrupalCI/Plugin/BuildTypes/<jobtype>->defaultArguments
    $buildtype_defaults = $build->getDefaultArguments();
    $this->dciVariables->add($buildtype_defaults, 'default');
    if (!empty($buildtype_defaults)) {
      // OPUT
      $this->io->writeLn("<comment>Loading build type default arguments:</comment>");
      $this->io->writeLn(implode(",", array_keys($buildtype_defaults)));
    }

    // 3. Local overrides defined in ~/.drupalci/config
    $confighelper = new ConfigHelper();
    $local_overrides = $confighelper->getCurrentConfigSetParsed();
    $this->dciVariables->add($local_overrides, 'local');
    if (!empty($local_overrides)) {
      // OPUT
      $this->io->writeLn("<comment>Loading local DrupalCI environment config override arguments.</comment>");
      $this->io->writeLn(implode(",", array_keys($local_overrides)));
    }

    // 4. 'DCI_' namespaced environment variable overrides
    $environment_variables = $confighelper->getCurrentEnvVars();
    $this->dciVariables->add($environment_variables, 'environment');
    if (!empty($environment_variables)) {
      // OPUT
      $this->io->writeLn("<comment>Loading local namespaced environment variable override arguments.</comment>");
      $this->io->writeLn(implode(",", array_keys($environment_variables)));
    }

    // 5. Additional variables passed in via the command line
    // TODO: Not yet implemented
    $cli_variables = ['DCI_BuildId' => $build->getBuildId()];
    $this->dciVariables->add($cli_variables, 'default');

    // Reorder array, placing priority variables at the front
/*    if (!empty($build->priorityArguments)) {
      $original_array = $dci_variables;
      $original_keys = array_keys($original_array);
      $ordered_variables = [];
      foreach ($build->priorityArguments as $element) {
        if (in_array($element, $original_keys)) {
          $ordered_variables[$element] = $original_array[$element];
          unset($original_array[$element]);
        }
      }
      $dci_variables = array_merge($ordered_variables, $original_array);
    }*/
  }

  /**
   * Execute Variable preprocessor Plugin logic
   */
  protected function executeVariablePreprocessors() {
    $dci_variables = $this->getDCIVariables();
    $plugin_manager = $this->getPreprocessPluginManager();
    foreach ($dci_variables as $key => &$value) {
      if (preg_match('/^DCI_(.+)$/i', $key, $matches)) {
        $name = strtolower($matches[1]);
        if ($plugin_manager->hasPlugin('variable', $name)) {
          /** @var \DrupalCI\Plugin\Preprocess\VariableInterface $plugin */
          $plugin = $plugin_manager->getPlugin('variable', $name);
          $new_keys = $plugin->target();
          if (!is_array($new_keys)) {
            $new_keys = [$new_keys];
          }
          // @TODO: error handling.
          foreach ($new_keys as $new_key) {
            // Only process variable plugins if the variable being changed actually exists.
            if (!empty($dci_variables[$new_key])) {
              $dci_variables[$new_key] = $plugin->process($dci_variables[$new_key], $value, $new_key);
            }
          }
        }
      }
    }
    $this->setDCIVariables($dci_variables);
  }

  /**
   * Execute Variable preprocessor Plugin logic
   */
  protected function executeDefinitionPreprocessors() {
    $definition = $this->getDefinition();
    $dci_variables = $this->getDCIVariables();
    $plugin_manager = $this->getPreprocessPluginManager();
    // Foreach DCI_* pair in the array, check if a definition plugin exists,
    // and process if it does.  We pass in the test definition template and
    // complete array of DCI_* variables.
    foreach ($dci_variables as $key => $value) {
      if (preg_match('/^DCI_(.+)$/', $key, $matches)) {
        $name = strtolower($matches[1]);
        if ($plugin_manager->hasPlugin('definition', $name)) {
          $plugin_manager->getPlugin('definition', $name)
            ->process($definition, $value, $dci_variables);
        }
      }
    }
    $this->setDefinition($definition);
  }

  /**
   * Substitute DCI_* variables into the build definition template
   */
  protected function substituteTemplateVariables() {
    // Generate our replacements array
    $replacements = [];
    $dci_variables = $this->getDCIVariables();
    foreach ($dci_variables as $key => $value) {
      if (preg_match('/^DCI_(.+)$/', $key, $matches)) {
        $name = strtolower($matches[1]);
        $replacements["%$key%"] = $value;
      }
    }

    // Add support for substituting '%HOME%' with the $HOME env variable
    $replacements["%HOME%"] = getenv("HOME");

    // Process DCI_* variable substitution into test definition template
    $search = array_keys($replacements);
    $replace = array_values($replacements);
    $definition = $this->getDefinition();
    array_walk_recursive($definition, function (&$value) use ($search, $replace) {
      $value = str_ireplace($search, $replace, $value);
    });

    // Save our post-replacements build definition back to the object
    $this->setDefinition($definition);
  }

  protected function parseBuildSteps() {
    $definition = $this->getDefinition();
    $build_steps = [];
    foreach ($definition as $stage => $steps) {
      $build_steps[$stage] = [];
      if (!empty($steps)) {
        foreach ($steps as $step => $data) {
          $build_steps[$stage][$step] = "";
        }
      }
    }
    return $build_steps;
  }


  /**
   * @return \DrupalCI\Plugin\PluginManager
   */
  protected function getPreprocessPluginManager() {
    return $this->preprocessPluginManager;
  }

}
