<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildPhase\BuildPhaseInterface;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("validate_codebase")
 */
class CodebaseValidateBuildPhase extends PluginBase implements BuildPhaseInterface, BuildTaskInterface  {

}
