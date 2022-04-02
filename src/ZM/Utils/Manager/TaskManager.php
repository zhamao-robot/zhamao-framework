<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace ZM\Utils\Manager;

use ZM\Console\Console;

class TaskManager
{
    /**
     * @param  string      $task_name 任务名称
     * @param  int         $timeout   超时时间
     * @param  mixed       ...$params 传递参数
     * @return false|mixed 执行结果（如果执行失败返回false，否则为执行结果）
     */
    public static function runTask(string $task_name, int $timeout = -1, ...$params)
    {
        if (!isset(server()->setting['task_worker_num'])) {
            Console::warning(zm_internal_errcode('E00056') . '未开启 TaskWorker 进程，请先修改 global 配置文件启用！');
            return false;
        }
        $r = server()->taskwait(['task' => $task_name, 'params' => $params], $timeout);
        return $r === false ? false : $r['result'];
    }
}
