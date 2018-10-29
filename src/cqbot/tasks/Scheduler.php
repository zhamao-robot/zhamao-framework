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

    /**
     * ScheduleTask constructor.
     * @param Framework $framework
     */
    public function __construct(Framework $framework) {
        $this->framework = $framework;
        self::$obj = $this;
    }

    public static function getInstance() {
        return self::$obj;
    }

    public function tick($id, $tick_time) {
        /** @var array $ls */
        $ls = Buffer::get("mods");
        foreach ($ls as $v) {
            if (in_array("onTick", get_class_methods($v))) {
                $v::onTick($tick_time - $this->framework->run_time);
            }
        }
    }
}