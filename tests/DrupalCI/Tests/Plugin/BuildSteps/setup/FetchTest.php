<?php

/**
 * @file
 * Contains \DrupalCI\Tests\Plugin\BuildSteps\setup\FetchTest.
 */

namespace DrupalCI\Tests\Plugin\BuildSteps\setup;

use DrupalCI\Plugin\BuildSteps\setup\Fetch;
use DrupalCI\Tests\DrupalCITestCase;
use GuzzleHttp\ClientInterface;

/**
 * @coversDefaultClass DrupalCI\Plugin\BuildSteps\setup\Fetch
 */
class FetchTest extends DrupalCITestCase {

  /**
   * @covers ::run
   */
  public function testRun() {
    $file = 'file.patch';
    $url = 'http://example.com/site/dir/' . $file;
    $dir = 'test/dir';

    $request = $this->getMock('GuzzleHttp\Message\RequestInterface');

    $http_client = $this->getMock('GuzzleHttp\ClientInterface');
    $http_client->expects($this->once())
      ->method('get')
      ->with($url, ['save_to' => "$dir/$file"])
      ->will($this->returnValue($request));

    $codebase = $this->getMock('DrupalCI\Build\CodeBase\Codebase');
    $build = $this->getMockBuilder('DrupalCI\Build\BuildInterface')
      ->setMethods(['getCodebase'])
      ->getMockForAbstractClass();
    $build->expects($this->once())
      ->method('getCodebase')
      ->will($this->returnValue($codebase));

    $fetch = new TestFetch();
    $fetch->setValidate($dir);
    $fetch->setHttpClient($http_client);
    $fetch->run($build, [['url' => $url]]);
  }
}

class TestFetch extends Fetch {
  use TestSetupBaseTrait;

  function setHttpClient(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }
}
