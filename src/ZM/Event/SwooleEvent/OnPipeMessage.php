<?php

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace ZM\Event\SwooleEvent;

use Swoole\Server;
use Throwable;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\Console\Console;
use ZM\Event\SwooleEvent;
use ZM\Utils\Manager\WorkerManager;

/**
 * Class OnPipeMessage
 * @SwooleHandler("PipeMessage")
 */
class OnPipeMessage implements SwooleEvent
{
    public function onCall(Server $server, $src_worker_id, $data)
    {
        $data = json_decode($data, true);
        try {
            WorkerManager::workerAction($src_worker_id, $data);
        } catch (Throwable $e) {
            $error_msg = $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')';
            Console::error(zm_internal_errcode('E00021') . 'Uncaught ' . get_class($e) . ' when calling "pipeMessage": ' . $error_msg);
            Console::trace();
        }
    }
}
