<?php


namespace ZM\Event\SwooleEvent;


use Error;
use Exception;
use Swoole\Server;
use Swoole\Server\Task;
use Throwable;
use ZM\Annotation\Swoole\OnTaskEvent;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Event\SwooleEvent;

/**
 * Class OnTask
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("task")
 */
class OnTask implements SwooleEvent
{
    /**
     * @noinspection PhpUnreachableStatementInspection
     * @noinspection PhpUnusedParameterInspection
     * @param Server|null $server
     * @param Task $task
     */
    public function onCall(?Server $server, Task $task) {
        if (isset($task->data["task"])) {
            $dispatcher = new EventDispatcher(\ZM\Annotation\Swoole\OnTask::class);
            $dispatcher->setRuleFunction(function ($v) use ($task) {
                /** @var \ZM\Annotation\Swoole\OnTask $v */
                return $v->task_name == $task->data["task"];
            });
            $dispatcher->setReturnFunction(function ($return) {
                EventDispatcher::interrupt($return);
            });
            $params = $task->data["params"];
            try {
                $dispatcher->dispatchEvents(...$params);
            } catch (Throwable $e) {
                $finish["throw"] = $e;
            }
            if ($dispatcher->status === EventDispatcher::STATUS_EXCEPTION) {
                $finish["result"] = null;
                $finish["retcode"] = -1;
            } else {
                $finish["result"] = $dispatcher->store;
                $finish["retcode"] = 0;
            }
            if (zm_atomic("server_is_stopped")->get() === 1) {
                return;
            }
            $task->finish($finish);
        } else {
            try {
                $dispatcher = new EventDispatcher(OnTaskEvent::class);
                $dispatcher->setRuleFunction(function ($v) {
                    /** @var OnTaskEvent $v */
                    return eval("return " . $v->getRule() . ";");
                });
                $dispatcher->dispatchEvents();
            } catch (Exception $e) {
                $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                Console::error(zm_internal_errcode("E00026") . "Uncaught exception " . get_class($e) . " when calling \"task\": " . $error_msg);
                Console::trace();
            } catch (Error $e) {
                $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
                Console::error(zm_internal_errcode("E00026") . "Uncaught " . get_class($e) . " when calling \"task\": " . $error_msg);
                Console::trace();
            }
        }
    }
}