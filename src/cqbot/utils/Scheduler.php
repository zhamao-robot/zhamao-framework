<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/6/3
 * Time: 下午2:06
 */

class Scheduler
{
    /** @var null|Scheduler */
    public static $obj = null;

    private $framework;

    private $start_time;

    /**
     * ScheduleTask constructor.
     * @param Framework $framework
     * @param $start_time
     */
    public function __construct(Framework $framework, $start_time) {
        $this->framework = $framework;
        self::$obj = $this;
        $this->start_time = $start_time;
    }

    public static function getInstance() {
        return self::$obj;
    }

    public function tick($id) {
        //Console::info("Timer ".Console::setColor("#".$id, "gold").":".Cache::$server->worker_id." ticking at ".time());
        /** @var array $ls */
        $ls = Cache::get("mods");
        foreach ($ls as $v) {
            if (in_array("onTick", get_class_methods($v))) {
                $v::onTick(time() - $this->start_time, $id);
            }
        }
    }
}