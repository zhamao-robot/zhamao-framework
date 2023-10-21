<?php

declare(strict_types=1);

namespace ZM\API;

use Closure;
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
        if ($connection->getOption('type') === CONN_WEBSOCKET) {
            return $this->processWebsocketAPI($connection, $reply, $function);
        }

        return $this->processHttpAPI($connection, $reply, $function);
    }

    private function processWebsocketAPI($connection, $reply, $function = false)
    {
        try {
            $api_id = ZMAtomic::get('wait_msg_id')->add();
            $reply['echo'] = $api_id;
            $fd = $connection->getFd();
            $send_func = function () use ($fd, $reply) {
                if (!server()->push($fd, json_encode($reply))) {
                    throw new \Exception('CQAPI send failed, websocket push error.');
                }
            };
            if ($function === true) {
                $obj = [
                    'data' => $reply,
                    'time' => microtime(true),
                    'self_id' => $connection->getOption('connect_id'),
                    'echo' => $api_id,
                ];
                return CoMessage::yieldByWS($obj, ['echo'], 10, $send_func);
            } else {
                $send_func();
                return true;
            }
        } catch (\Exception $e) {
            Console::warning(zm_internal_errcode('E00036') . $e->getMessage());
        }
        if ($function === true) {
            SpinLock::lock('wait_api');
            $r = LightCacheInside::get('wait_api', 'wait_api');
            if (isset($r[$reply['echo']])) {
                unset($r[$reply['echo']]);
                LightCacheInside::set('wait_api', 'wait_api', $r);
            }
            SpinLock::unlock('wait_api');
            $response = [
                'status' => 'failed',
                'retcode' => -1000,
                'data' => null,
                'self_id' => $connection->getOption('connect_id'),
            ];
            return $response;
        } else {
            return false;
        }
    }

    private function processHttpAPI($connection, $reply, $function = null): bool
    {
        unset($connection, $reply, $function);
        // TODO: HTTP方式处理API的代码还没写
        return false;
    }
}
