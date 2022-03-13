<?php /** @noinspection PhpUnusedParameterInspection */


namespace ZM\Event\SwooleEvent;


use Error;
use Exception;
use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Utils\Manager\WorkerManager;

/**
 * Class OnPipeMessage
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("PipeMessage")
 */
class OnPipeMessage implements SwooleEvent
{
    public function onCall(Server $server, $src_worker_id, $data) {
        $data = json_decode($data, true);
        try {
            WorkerManager::workerAction($src_worker_id, $data);
        } catch (Exception $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error(zm_internal_errcode("E00021") . "Uncaught exception " . get_class($e) . " when calling \"pipeMessage\": " . $error_msg);
            Console::trace();
        } catch (Error $e) {
            $error_msg = $e->getMessage() . " at " . $e->getFile() . "(" . $e->getLine() . ")";
            Console::error(zm_internal_errcode("E00021") . "Uncaught " . get_class($e) . " when calling \"pipeMessage\": " . $error_msg);
            Console::trace();
        }
    }
}