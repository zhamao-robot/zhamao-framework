<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\Routing\RouteCollection;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Http\RequestMethod;
use ZM\Utils\HttpUtil;
use PHPUnit\Framework\TestCase;
use ZM\Utils\Manager\RouteManager;

class HttpUtilTest extends TestCase
{
    /**
     * @dataProvider providerTestHandleStaticPage
     */
    public function testHandleStaticPage(string $page, bool $expected): void
    {
        $swoole_response = $this->getMockClass(Response::class);
        $r = new \ZM\Http\Response(new $swoole_response());
        HttpUtil::handleStaticPage($page, $r);
        $this->assertEquals($expected, $r->getStatusCode() === 200);
    }

    public function providerTestHandleStaticPage(): array
    {
        return [
            'exists page' => ['/static.html', true],
            'not exists page' => ['/not_exists.html', false],
        ];
    }

    /**
     * @covers       \ZM\Utils\HttpUtil::getHttpCodePage
     * @covers       \ZM\Utils\HttpUtil::responseCodePage
     * @dataProvider providerTestGetHttpCodePage
     */
    public function testGetHttpCodePage(int $code, bool $expected): void
    {
        $has_response = !empty(HttpUtil::getHttpCodePage($code));
        $this->assertSame($expected, $has_response);
    }

    public function providerTestGetHttpCodePage(): array
    {
        return [
            'code 404' => [404, true],
            'code 500' => [500, false],
            'code 403' => [403, false],
        ];
    }

    public function testParseUri(): void
    {
        RouteManager::$routes = new RouteCollection();
        RouteManager::importRouteByAnnotation(
            new RequestMapping('/test', 'test', RequestMethod::GET),
            __FUNCTION__,
            __CLASS__,
            []
        );
        $r = new Request();
        $r->server['request_uri'] = '/test';
        $r->server['request_method'] = 'GET';
        $this->assertTrue(HttpUtil::parseUri(
            $r,
            null,
            '/test',
            $node,
            $params
        ));
    }
}
