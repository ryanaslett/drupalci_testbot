<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildPhase\BuildPhaseInterface;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("startcontainers")
 */

class StartContainersBuildPhase extends PluginBase implements BuildPhaseInterface, BuildTaskInterface  {

}
