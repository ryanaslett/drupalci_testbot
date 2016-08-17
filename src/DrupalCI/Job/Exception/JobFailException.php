<?php

namespace DrupalCI\Job\Exception;

use DrupalCI\Job\Exception\JobException;

class JobFailException extends JobException {

  public function __construct($message) {
    parent::__construct($message, 1);
  }

}
