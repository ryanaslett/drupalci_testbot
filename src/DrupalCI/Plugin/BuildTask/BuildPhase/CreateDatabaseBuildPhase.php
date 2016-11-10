<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildPhase\BuildPhaseInterface;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("create_db")
 */
class CreateDatabaseBuildPhase extends PluginBase implements BuildPhaseInterface, BuildTaskInterface {

}
