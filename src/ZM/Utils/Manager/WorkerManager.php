<?php

namespace ZM\Utils\Manager;

use Exception;
use Swoole\Coroutine;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\Swoole\OnPipeMessageEvent;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Event\EventManager;
use ZM\Store\LightCache;
use ZM\Store\LightCacheInside;
use ZM\Store\WorkerCache;

class WorkerManager
{
    /**
     * Worker 进程间通信触发的动作类型函数
     * @param $src_worker_id
     * @param $data
     * @throws Exception
     */
    public static function workerAction($src_worker_id, $data)
    {
        $server = server();
        switch ($data["action"] ?? '') {
            case 'add_short_command':
                Console::verbose("Adding short command " . $data["data"][0]);
                $obj = new CQCommand();
                $obj->method = quick_reply_closure($data["data"][1]);
                $obj->match = $data["data"][0];
                $obj->class = "";
                EventManager::addEvent(CQCommand::class, $obj);
                break;
            case "eval":
                eval($data["data"]);
                break;
            case "call_static":
                call_user_func_array([$data["data"]["class"], $data["data"]["method"]], $data["data"]["params"]);
                break;
            case "save_persistence":
                LightCache::savePersistence();
                break;
            case "resume_ws_message":
                $obj = $data["data"];
                Coroutine::resume($obj["coroutine"]);
                break;
            case "getWorkerCache":
                $r = WorkerCache::get($data["key"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "setWorkerCache":
                $r = WorkerCache::set($data["key"], $data["value"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "unsetWorkerCache":
                $r = WorkerCache::unset($data["key"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "hasKeyWorkerCache":
                $r = WorkerCache::hasKey($data["key"], $data["subkey"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "asyncAddWorkerCache":
                WorkerCache::add($data["key"], $data["value"], true);
                break;
            case "asyncSubWorkerCache":
                WorkerCache::sub($data["key"], $data["value"], true);
                break;
            case "asyncSetWorkerCache":
                WorkerCache::set($data["key"], $data["value"], true);
                break;
            case "asyncUnsetWorkerCache":
                WorkerCache::unset($data["key"], true);
                break;
            case "addWorkerCache":
                $r = WorkerCache::add($data["key"], $data["value"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "subWorkerCache":
                $r = WorkerCache::sub($data["key"], $data["value"]);
                $action = ["action" => "returnWorkerCache", "cid" => $data["cid"], "value" => $r];
                $server->sendMessage(json_encode($action, 256), $src_worker_id);
                break;
            case "returnWorkerCache":
                WorkerCache::$transfer[$data["cid"]] = $data["value"];
                zm_resume($data["cid"]);
                break;
            default:
                $dispatcher = new EventDispatcher(OnPipeMessageEvent::class);
                $dispatcher->setRuleFunction(function (OnPipeMessageEvent $v) use ($data) {
                    return $v->action == $data["action"];
                });
                $dispatcher->dispatchEvents($data);
                break;
        }
    }

    /**
     * 给 Worker 进程发送动作指令（包括自身，自身将直接执行）
     * @param $worker_id
     * @param $action
     * @param $data
     * @throws Exception
     */
    public static function sendActionToWorker($worker_id, $action, $data)
    {
        $obj = ["action" => $action, "data" => $data];
        if (server()->worker_id === -1 && server()->getManagerPid() != posix_getpid()) {
            Console::warning(zm_internal_errcode("E00022") . "Cannot send worker action from master or manager process!");
            return;
        }
        if (server()->worker_id == $worker_id) {
            self::workerAction($worker_id, $obj);
        } else {
            server()->sendMessage(json_encode($obj), $worker_id);
        }
    }

    /**
     * 向所有 Worker 进程发送动作指令
     */
    public static function resumeAllWorkerCoroutines()
    {
        if (server()->worker_id === -1) {
            Console::warning("Cannot call '" . __FUNCTION__ . "' in non-worker process!");
            return;
        }
        foreach ((LightCacheInside::get("wait_api", "wait_api") ?? []) as $v) {
            if (isset($v["coroutine"], $v["worker_id"])) {
                if (server()->worker_id == $v["worker_id"]) Coroutine::resume($v["coroutine"]);
            }
        }
    }
}