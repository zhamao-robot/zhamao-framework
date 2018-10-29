<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/10/15
 * Time: 11:06 AM
 */

class MetaEvent extends Event
{
    public function __construct($req) {
        switch ($req["meta_event_type"]) {
            case "lifecycle":
                //插件生命周期事件
                switch ($req["sub_type"]) {
                    case "enable":
                        Console::info("机器人" . $req["self_id"] . "的HTTP插件" . Console::setColor("启动", "green") . "运行");
                        break;
                    case "disable":
                        Console::info("机器人" . $req["self_id"] . "的HTTP插件" . Console::setColor("停止", "red") . "运行");
                        break;
                }
                break;
            case "heartbeat":
                //处理心跳包中的status事件目前炸毛不需要。
                break;
        }
    }
}