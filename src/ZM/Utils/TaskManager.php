<?php


namespace ZM\Utils;


class TaskManager
{
    public static function runTask($task_name, $timeout = -1, ...$params) {
        $r = server()->taskwait(["task" => $task_name, "params" => $params], $timeout);
        return $r === false ? false : $r["result"];
    }
}