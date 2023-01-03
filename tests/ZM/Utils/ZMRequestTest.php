<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ZM\Utils\ZMRequest;

/**
 * @internal
 */
class ZMRequestTest extends TestCase
{
    public function testPost()
    {
        $this->markTestIncomplete('Potential dead on Windows');
//        $r = ZMRequest::post('http://httpbin.org/post', [], 'niubi=123');
//        $this->assertStringContainsString('123', $r);
//        $r2 = ZMRequest::post('http://httpbin.org/post', ['User-Agent' => 'test'], 'oijoij=ooo', [], false);
//        $this->assertInstanceOf(ResponseInterface::class, $r2);
//        $this->assertStringContainsString('ooo', $r2->getBody()->getContents());
    }

    public function testGet()
    {
        $this->markTestIncomplete('Potential dead on Windows');
//        $r = ZMRequest::get('http://httpbin.org/get', [
//            'X-Test' => '123',
//        ]);
//        $this->assertStringContainsString('123', $r);
    }
}
