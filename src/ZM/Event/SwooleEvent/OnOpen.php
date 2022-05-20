<?php

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Closure;
use Error;
use Exception;
use Swoole\Coroutine;
use Swoole\Http\Request;
use ZM\Annotation\Swoole\OnOpenEvent;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Config\ZMConfig;
use ZM\ConnectionManager\ManagerGM;
use ZM\Console\Console;
use ZM\Container\ContainerServicesProvider;
use ZM\Context\Context;
use ZM\Event\EventDispatcher;
use ZM\Event\SwooleEvent;
use ZM\Store\LightCacheInside;

/**
 * Class OnOpen
 * @SwooleHandler("open")
 */
class OnOpen implements SwooleEvent
{
    public function onCall($server, Request $request)
    {
        Console::debug('Calling Swoole "open" event from fd=' . $request->fd);
        unset(Context::$context[Coroutine::getCid()]);

        $type = strtolower($request->header['x-client-role'] ?? $request->get['type'] ?? '');
        $access_token = explode(' ', $request->header['authorization'] ?? '')[1] ?? $request->get['token'] ?? '';
        $token = ZMConfig::get('global', 'access_token');
        if ($token instanceof Closure) {
            if (!$token($access_token)) {
                $server->close($request->fd);
                Console::warning(zm_internal_errcode('E00018') . 'Unauthorized access_token: ' . $access_token);
                return;
            }
        } elseif (is_string($token)) {
            if ($access_token !== $token && $token !== '') {
                $server->close($request->fd);
                Console::warning(zm_internal_errcode('E00019') . 'Unauthorized access_token: ' . $access_token);
                return;
            }
        }
        $type_conn = ManagerGM::getTypeClassName($type);
        ManagerGM::pushConnect($request->fd, $type_conn);
        $conn = ManagerGM::get($request->fd);
        set_coroutine_params(['server' => $server, 'request' => $request, 'connection' => $conn, 'fd' => $request->fd]);
        $conn->setOption('connect_id', (string) ($request->header['x-self-id'] ?? ''));

        resolve(ContainerServicesProvider::class)->registerServices('connection');

        $dispatcher1 = new EventDispatcher(OnOpenEvent::class);
        $dispatcher1->setRuleFunction(function ($v) {
            return ctx()->getConnection()->getName() == $v->connect_type && eval('return ' . $v->getRule() . ';');
        });

        $dispatcher = new EventDispatcher(OnSwooleEvent::class);
        $dispatcher->setRuleFunction(function ($v) {
            if ($v->getRule() == '') {
                return strtolower($v->type) == 'open';
            }
            if (strtolower($v->type) == 'open' && eval('return ' . $v->getRule() . ';')) {
                return true;
            }
            return false;
        });
        try {
            $obb_onebot = ZMConfig::get('global', 'onebot') ??
                ZMConfig::get('global', 'modules')['onebot'] ??
                ['status' => true, 'single_bot_mode' => false, 'message_level' => 99];
            $onebot_status = $obb_onebot['status'];
            if ($conn->getName() === 'qq' && $onebot_status === true) {
                if ($obb_onebot['single_bot_mode']) {
                    LightCacheInside::set('connect', 'conn_fd', $request->fd);
                }
            }
            $dispatcher1->dispatchEvents($conn);
            $dispatcher->dispatchEvents($conn);
        } catch (Exception $e) {
            $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
            Console::error(zm_internal_errcode('E00020') . 'Uncaught exception ' . get_class($e) . ' when calling "open": ' . $error_msg);
            Console::trace();
        } catch (Error $e) {
            $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
            Console::error(zm_internal_errcode('E00020') . 'Uncaught ' . get_class($e) . ' when calling "open": ' . $error_msg);
            Console::trace();
        } finally {
            resolve(ContainerServicesProvider::class)->cleanup();
        }
    }
}
