<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/26
 * Time: 下午3:15
 */

class WorkerStartEvent extends ServerEvent
{
    public function __construct(swoole_server $server, $worker_id) {
        parent::__construct($server);
        Cache::$server = $server;
        load_extensions();
        Cache::$reload_time->add(1);
        CQUtil::loadAllFiles();
        $set = settings();
        foreach (get_included_files() as $file)
            Console::debug("Loaded " . $file);
        //计时器（ms）
        if ($set["swoole_use_tick"] === true) {
            Cache::$scheduler = new Scheduler($this->getFramework(), time());
            //$timer_id = $server->tick($set["swoole_tick_interval"], [Cache::$scheduler, "tick"]);
            Console::info("已在worker #" . $worker_id . " 中成功启动了计时器！计时器间隔：" . $set["swoole_tick_interval"]);
        }
        Console::debug("master_pid = " . $server->master_pid);
        Console::debug("worker_id = " . $worker_id);
        Console::put("===== Worker " . Console::setColor("#" . $worker_id, "gold") . " 已启动 =====");
    }
}