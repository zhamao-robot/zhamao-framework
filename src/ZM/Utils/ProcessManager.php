<?php /** @noinspection PhpUnused */


namespace ZM\Utils;


use Co;
use ZM\Annotation\Swoole\OnPipeMessageEvent;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Store\LightCache;
use ZM\Store\LightCacheInside;
use ZM\Store\WorkerCache;

class ProcessManager
{
    public static function workerAction($src_worker_id, $data) {
        $server = server();
        switch ($data["action"] ?? '') {
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
                Co::resume($obj["coroutine"]);
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

    public static function sendActionToWorker($worker_id, $action, $data) {
        $obj = ["action" => $action, "data" => $data];
        if (server()->worker_id === -1 && server()->getManagerPid() != posix_getpid()) {
            Console::warning("Cannot send worker action from master or manager process!");
            return;
        }
        if (server()->worker_id == $worker_id) {
            self::workerAction($worker_id, $obj);
        } else {
            server()->sendMessage(json_encode($obj), $worker_id);
        }
    }

    public static function resumeAllWorkerCoroutines() {
        if (server()->worker_id === -1) {
            Console::warning("Cannot call '".__FUNCTION__."' in non-worker process!");
            return;
        }
        foreach ((LightCacheInside::get("wait_api", "wait_api") ?? []) as $k => $v) {
            if (($v["result"] ?? false) === null && isset($v["coroutine"], $v["worker_id"])) {
                if (server()->worker_id == $v["worker_id"]) Co::resume($v["coroutine"]);
            }
        }
    }
}