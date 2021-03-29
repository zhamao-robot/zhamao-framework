<?php /** @noinspection PhpUnusedParameterInspection */


namespace ZM\Event\SwooleEvent;


use Swoole\Server;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Event\SwooleEvent;
use ZM\Utils\ProcessManager;

/**
 * Class OnPipeMessage
 * @package ZM\Event\SwooleEvent
 * @SwooleHandler("PipeMessage")
 */
class OnPipeMessage implements SwooleEvent
{
    public function onCall(Server $server, $src_worker_id, $data) {
        $data = json_decode($data, true);
        ProcessManager::workerAction($src_worker_id, $data);
    }
}