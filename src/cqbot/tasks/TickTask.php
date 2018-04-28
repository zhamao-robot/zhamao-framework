<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/28
 * Time: 下午4:00
 */

class TickTask
{
    public function __construct(Framework $framework, $timer_id) {
        $interval = ($framework->tick_time - $framework->run_time);
        if ($interval % 900 == 0) CQUtil::saveAllFiles();//15分钟存一次数据

        //这里可以放置你的定时器内执行的功能，自由扩展
    }
}