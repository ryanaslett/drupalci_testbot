<?php

namespace DrupalCI\Plugin\BuildTask\BuildStage;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildStage\BuildStageInterface;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("codebase")
 */

class CodebaseBuildStage extends PluginBase  implements BuildStageInterface, BuildTaskInterface  {

}
