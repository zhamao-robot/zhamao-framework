<?php

declare(strict_types=1);

namespace ZM\API;

use Closure;
use ZM\Adapters\AdapterInterface;
use ZM\Console\Console;
use ZM\Store\LightCacheInside;
use ZM\Store\Lock\SpinLock;
use ZM\Store\ZMAtomic;
use ZM\Utils\CoMessage;

trait CQAPI
{
    /** @var null|Closure 过滤用的 */
    private static $filter;

    public function __call($name, $arguments)
    {
        return false;
    }

    public static function registerFilter(callable $callable)
    {
        self::$filter = $callable;
    }

    public function getActionName($suffix, string $method)
    {
        $postfix = ($suffix == OneBotV11::API_ASYNC ? '_async' : ($suffix == OneBotV11::API_RATE_LIMITED ? '_rate_limited' : ''));
        $func_name = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $method));
        return $func_name . $postfix;
    }

    private function processAPI($connection, $reply, $function = null)
    {
        if (is_callable(self::$filter)) {
            $reply2 = call_user_func(self::$filter, $reply);
            if (is_bool($reply2)) {
                return $reply2;
            }
            $reply = $reply2;
        }
        $api_id = (string) ZMAtomic::get('wait_msg_id')->add();
        if ($connection->getOption('type') === CONN_WEBSOCKET) {
            // TODO: remove this
            if ($function !== true && $function !== null) {
                logger()->warning('possible deprecated parameter: {function}, please report to dev for further develop', ['function' => $function]);
            }
            return app(AdapterInterface::class)->handleOutgoingRequest($reply['action'], $reply['params'] ?? [], $api_id);
        }

        return $this->processHttpAPI($connection, $reply, $function);
    }

    private function processWebsocketAPI($connection, $reply, $function = false)
    {
        $api_id = ZMAtomic::get('wait_msg_id')->add();
        $reply['echo'] = $api_id;
        if (server()->push($connection->getFd(), json_encode($reply))) {
            if ($function === true) {
                $obj = [
                    'data' => $reply,
                    'time' => microtime(true),
                    'self_id' => $connection->getOption('connect_id'),
                    'echo' => $api_id,
                ];
                return CoMessage::yieldByWS($obj, ['echo'], 30);
            }
            return true;
        }
        Console::warning(zm_internal_errcode('E00036') . 'CQAPI send failed, websocket push error.');
        $response = [
            'status' => 'failed',
            'retcode' => -1000,
            'data' => null,
            'self_id' => $connection->getOption('connect_id'),
        ];
        SpinLock::lock('wait_api');
        $r = LightCacheInside::get('wait_api', 'wait_api');
        unset($r[$reply['echo']]);
        LightCacheInside::set('wait_api', 'wait_api', $r);
        SpinLock::unlock('wait_api');
        if ($function === true) {
            return $response;
        }
        return false;
    }

    private function processHttpAPI($connection, $reply, $function = null): bool
    {
        unset($connection, $reply, $function);
        // TODO: HTTP方式处理API的代码还没写
        return false;
    }
}
