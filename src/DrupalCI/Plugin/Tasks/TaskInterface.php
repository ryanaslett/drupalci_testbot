<?php
/**
 * Created by PhpStorm.
 * User: Ryan
 * Date: 8/16/16
 * Time: 11:39 PM
 */

namespace DrupalCI\Plugin\Tasks;


interface TaskInterface {

  public function run();
  public function getSignal();
  public function getOutput();
  public function getOutput();
  /*Have execution output (stdout)
Have Signal result ($!)
Has execution error (stderr)
Has a terse and verbose summary of result.
Generate artifacts
Collects error info like core dumps and other logs
Has a type (testing task, vs codebase task, vs Environment build task)
Get its configuration from the Job (or Build?)
Has an environment
*/

}
