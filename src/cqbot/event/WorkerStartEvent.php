<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午3:15
 */

class WorkerStartEvent extends Event
{
    public function __construct(swoole_server $server, $worker_id){

        Console::info("Starting worker: " . $worker_id);

        CQUtil::loadAllFiles();
        foreach (get_included_files() as $file)
            Console::debug("Loaded " . $file);

        //计时器（ms）
        $this->getFramework()->scheduler = new Scheduler($this->getFramework());
        $server->tick(1000, [$this->getFramework(), "processTick"]);

        Console::debug("master_pid = " . $server->master_pid);
        Console::debug("worker_id = " . $worker_id);
        Console::put("\n==========STARTUP DONE==========\n");
    }
}