<?php /** @noinspection PhpUnused */


namespace ZM\Utils;


class ProcessManager
{
    public static function runOnTask($param, $timeout = 0.5, $dst_worker_id = -1) {
        return server()->taskwait([
            "action" => "runMethod",
            "class" => $param["class"],
            "method" => $param["method"],
            "params" => $param["params"]
        ], $timeout, $dst_worker_id);
    }
}