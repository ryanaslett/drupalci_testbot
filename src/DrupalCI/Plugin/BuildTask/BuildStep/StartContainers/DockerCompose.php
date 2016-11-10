<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\StartContainers;


use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\PluginBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;

/**
 * @PluginID("docker_compose")
 */
class DockerCompose extends PluginBase implements BuildStepInterface, BuildTaskInterface {

}
