<?php

declare(strict_types=1);

namespace ZM\Event\Listener;

use OneBot\Driver\Event\WebSocket\WebSocketCloseEvent;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use OneBot\Driver\Process\ProcessManager;
use OneBot\Http\HttpFactory;
use OneBot\Util\Singleton;
use ZM\Container\ContainerServicesProvider;
use ZM\Process\ProcessStateManager;

class WSEventListener
{
    use Singleton;

    private static int $ws_counter = 0;

    private static array $conn_handle = [];

    public function onWebSocketOpen(WebSocketOpenEvent $event)
    {
        // 计数，最多只能接入 1024 个连接，为了适配多进程
        ++self::$ws_counter;
        if (self::$ws_counter >= 1024) {
            $event->withResponse(HttpFactory::getInstance()->createResponse(503));
            return;
        }
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
            if (ProcessStateManager::$process_mode['worker'] > 1) {
                // 如果开了多 Worker，则需要将连接信息写入文件，以便跨进程读取
                $info = ['impl' => $line[1] ?? 'unknown'];
                self::$conn_handle[$event->getFd()] = $info;
                file_put_contents(zm_dir(ZM_STATE_DIR . '/.WS' . $event->getFd() . '.' . ProcessManager::getProcessId()), json_encode($info));
            }
        }
    }

    public function onWebSocketClose(WebSocketCloseEvent $event)
    {
        --self::$ws_counter;
        // 删除连接信息
        $fd = $event->getFd();
        $filename = zm_dir(ZM_STATE_DIR . '/.WS' . $fd . '.' . ProcessManager::getProcessId());
        if (file_exists($filename)) {
            unlink($filename);
        }
        unset(self::$conn_handle[$fd]);
        resolve(ContainerServicesProvider::class)->cleanup();
    }
}
