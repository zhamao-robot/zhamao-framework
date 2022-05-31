<?php

declare(strict_types=1);

namespace ZM\Adapters\OneBot11;

use ZM\ConnectionManager\ConnectionObject;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Utils\CoMessage;

trait OneBot11OutgoingTrait
{
    public function handleOutgoingRequest(string $action, array $params, string $echo = null, array $extra = [])
    {
        logger()->debug('handling outgoing request action: ' . $action);
        $data = array_merge(compact('action', 'params', 'echo'), $extra);
        $fd = app(ConnectionObject::class)->getFd();

        if (server()->push($fd, json_encode($data))) {
            $hang = [
                'data' => $data,
                'time' => microtime(true),
                'self_id' => app(ConnectionObject::class)->getOption('connect_id'),
                'echo' => $echo,
            ];
            return CoMessage::yieldByWS($hang, ['echo'], 30);
        }

        logger()->error(zm_internal_errcode('E00036') . '动作请求发送失败：' . $action . '，WebSocket 推送失败');
        $response = [
            'status' => 'failed',
            'retcode' => -1000,
            'data' => null,
            'self_id' => app(ConnectionObject::class)->getOption('connect_id'),
        ];
        SpinLock::lock('wait_api');
        $r = LightCacheInside::get('wait_api', 'wait_api');
        unset($r[$echo]);
        LightCacheInside::set('wait_api', 'wait_api', $r);
        SpinLock::unlock('wait_api');
        return $response;
    }
}
