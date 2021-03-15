<?php


namespace ZM\Utils;


use ZM\Console\Console;

class TaskManager
{
    public static function runTask($task_name, $timeout = -1, ...$params) {
        if (!isset(server()->setting["task_worker_num"])) {
            Console::warning("未开启 TaskWorker 进程，请先修改 global 配置文件启用！");
            return false;
        }
        $r = server()->taskwait(["task" => $task_name, "params" => $params], $timeout);
        return $r === false ? false : $r["result"];
    }
}