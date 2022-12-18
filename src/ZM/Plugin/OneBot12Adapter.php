<?php

declare(strict_types=1);

namespace ZM\Plugin;

use Choir\Http\HttpFactory;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use ZM\Utils\ConnectionUtil;

class OneBot12Adapter extends ZMPlugin
{
    public function __construct()
    {
        parent::__construct(__DIR__);
        $this->addEvent(WebSocketOpenEvent::class, [$this, 'handleWSReverseInput']);
    }

    /**
     * 接入和认证反向 WS 的连接
     */
    public function handleWSReverseInput(WebSocketOpenEvent $event): void
    {
        // 判断是不是 OneBot 12 反向 WS 连进来的，通过 Sec-WebSocket-Protocol 头
        $line = explode('.', $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol'), 2);
        if ($line[0] === '12') {
            logger()->info('检测到 OneBot 12 反向 WS 连接，正在进行认证...');
            // 是 OneBot 12 标准的，准许接入，进行鉴权
            $request = $event->getRequest();
            if (($stored_token = $event->getSocketConfig()['access_token'] ?? '') !== '') {
                // 测试 Header
                $token = $request->getHeaderLine('Authorization');
                if ($token === '') {
                    // 测试 Query
                    $token = $request->getQueryParams()['access_token'] ?? '';
                }
                $token = explode('Bearer ', $token);
                $info = ['impl' => $line[1] ?? 'unknown'];
                if (!isset($token[1]) || $token[1] !== $stored_token) { // 没有 token，鉴权失败
                    logger()->warning('OneBot 12 反向 WS 连接鉴权失败，拒绝接入');
                    $event->withResponse(HttpFactory::createResponse(401, 'Unauthorized'));
                    return;
                }
            }
        }
        // 设置 OneBot 相关的东西
        ConnectionUtil::setConnection($event->getFd(), $info ?? []);
    }
}
