<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use OneBot\Http\HttpFactory;
use OneBot\Util\Singleton;
use ZM\Container\ContainerServicesProvider;

class WSEventListener
{
    use Singleton;

    public function onWebSocketOpen(WebSocketOpenEvent $event)
    {
        // 注册容器
        resolve(ContainerServicesProvider::class)->registerServices('connection');

        // 判断是不是 OneBot 12 反向 WS 连进来的，通过 Sec-WebSocket-Protocol 头
        $line = explode('.', $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol'), 2);
        if ($line[0] === '12') {
            // 是 OneBot 12 标准的，准许接入，进行鉴权
            $request = $event->getRequest();
            if (($stored_token = $event->getSocketConfig()['access_token'] ?? '') !== '') {
                $token = $request->getHeaderLine('Authorization');
                $token = explode('Bearer ', $token);
                if (!isset($token[1]) || $token[1] !== $stored_token) { // 没有 token，鉴权失败
                    $event->withResponse(HttpFactory::getInstance()->createResponse(401, 'Unauthorized'));
                    return;
                }
            }
            // 这里下面为连接准入，允许接入反向 WS，TODO
        }
    }
}
