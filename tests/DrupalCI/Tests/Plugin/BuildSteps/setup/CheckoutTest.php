<?php

/**
 * @file
 * Contains \DrupalCI\Tests\Plugin\BuildSteps\setup\CheckoutTest.
 */

namespace DrupalCI\Tests\Plugin\BuildSteps\setup;

use DrupalCI\Plugin\BuildSteps\setup\Checkout;
use DrupalCI\Tests\DrupalCITestCase;

class CheckoutTest extends DrupalCITestCase {

  public function testRunGitCheckout() {
    $dir = 'test/dir';
    $data = [
      'protocol' => 'git',
      'repo' => 'git://code.drupal.org/drupal.git',
      'branch' => '8.0.x',
      'checkout_dir' => $dir,
      'depth' => 1,
    ];
    $checkout = new TestCheckout();
    $checkout->setValidate($dir);
    $checkout->run($this->job, $data);
    $this->assertSame(['git clone -b 8.0.x --depth 1 git://code.drupal.org/drupal.git \'test/dir\''], $checkout->getCommands());
  }

  public function testRunLocalCheckout() {
    $dir = 'test/dir';
    $tmp_dir = sys_get_temp_dir();
    $data = [
      'protocol' => 'local',
      'source_dir' => $tmp_dir,
      'checkout_dir' => $dir,
    ];
    $checkout = new TestCheckout([], 'checkout', []);
    $checkout->setValidate($dir);
    $checkout->run($this->job, $data);
    $this->assertSame(["rsync -a   $tmp_dir/. test/dir"], $checkout->getCommands());
  }

}

class TestCheckout extends Checkout {
  use TestSetupBaseTrait;

}
