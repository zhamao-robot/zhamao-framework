<?php /** @noinspection PhpUnused */


namespace ZM\Utils\Manager;


use ZM\Console\Console;

class TaskManager
{
    /**
     * @param $task_name
     * @param int $timeout
     * @param mixed ...$params
     * @return false|mixed
     */
    public static function runTask($task_name, int $timeout = -1, ...$params) {
        if (!isset(server()->setting["task_worker_num"])) {
            Console::warning(zm_internal_errcode("E00056") . "未开启 TaskWorker 进程，请先修改 global 配置文件启用！");
            return false;
        }
        $r = server()->taskwait(["task" => $task_name, "params" => $params], $timeout);
        return $r === false ? false : $r["result"];
    }
}