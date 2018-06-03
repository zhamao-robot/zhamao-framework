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

        //API连接部分
        $this->getFramework()->api = new \swoole_http_client($this->getFramework()->host, $this->getFramework()->api_port);
        $this->getFramework()->api->set(['websocket_mask' => true]);
        $this->getFramework()->api->on('message', [$this->getFramework(), "onApiMessage"]);
        $this->getFramework()->api->on("close", function ($cli){
            Console::info(Console::setColor("API connection closed", "red"));
        });
        $this->getFramework()->api->upgrade('/api/', [$this->getFramework(), "onUpgrade"]);

        Console::debug("master_pid = " . $server->master_pid);
        Console::debug("worker_id = " . $worker_id);
        Console::put("\n==========STARTUP DONE==========\n");
    }
}