<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;
use Symfony\Component\Routing\RouteCollection;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Http\RequestMethod;
use ZM\Utils\HttpUtil;
use ZM\Utils\Manager\RouteManager;

/**
 * @internal
 */
class HttpUtilTest extends TestCase
{
    public function providerTestHandleStaticPage(): array
    {
        return [
            'exists page' => ['/static.html', true],
            'not exists page' => ['/not_exists.html', false],
        ];
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
