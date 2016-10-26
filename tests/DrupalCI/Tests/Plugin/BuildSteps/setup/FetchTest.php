<?php

/**
 * @file
 * Contains \DrupalCI\Tests\Plugin\BuildSteps\setup\FetchTest.
 */

namespace DrupalCI\Tests\Plugin\BuildSteps\setup;

use DrupalCI\Plugin\BuildTask\BuildStep\CodeBaseAssemble\Fetch;
use DrupalCI\Tests\DrupalCITestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use DrupalCI\Build\Codebase\CodeBase;
use DrupalCI\Build\BuildInterface;

/**
 * @coversDefaultClass DrupalCI\Plugin\BuildTask\BuildStep\CodeBaseAssemble\Fetch
 */
class FetchTest extends DrupalCITestCase {

  /**
   * @covers ::run
   */
  public function testRun() {
    $file = 'file.patch';
    $url = 'http://example.com/site/dir/' . $file;
    $dir = 'test/dir';

    $request = $this->getMock(RequestInterface::class);

    $http_client = $this->getMock(ClientInterface::class, array('get','send','sendAsync','request','requestAsync','getConfig'));
    $http_client->expects($this->once())
      ->method('get')
      ->with($url, ['save_to' => "$dir/$file"])
      ->will($this->returnValue($request));

    $codebase = $this->getMock(CodeBase::class);
    $build = $this->getMockBuilder(BuildInterface::class)
      ->setMethods(['getCodebase'])
      ->getMockForAbstractClass();
    $build->expects($this->once())
      ->method('getCodebase')
      ->will($this->returnValue($codebase));

    $data = [
      'files' => [['from' => "$url",'to' => "."]]
    ];
    $fetch = new TestFetch($data);
    $fetch->setValidate($dir);
    $fetch->setHttpClient($http_client);

    $fetch->run($build);
  }
}

class TestFetch extends Fetch {
  use TestSetupBaseTrait;

  function setHttpClient(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }
}
