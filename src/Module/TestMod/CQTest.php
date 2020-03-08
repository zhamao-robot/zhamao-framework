<?php


namespace Module\TestMod;


use Framework\Console;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQNotice;
use ZM\Annotation\Http\Controller;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Connection\CQConnection;
use ZM\ModBase;

/**
 * Class CQTest
 * @package Module\TestMod
 * @Controller("/view/test2")
 */
class CQTest extends ModBase
{
    /**
     * @SwooleEventAt(type="open",rule="connectType:qq")
     * @param CQConnection $conn
     */
    public function onRobotConnect($conn){
        Console::info("QQ robot: ".$conn->getQQ()." connected.");
    }

    /**
     * @CQCommand("多命令a")
     * @CQCommand(regexMatch="*是什么")
     * @param $arg
     * @return string
     * @throws \ZM\Exception\DbException
     */
    public function hello($arg) {
        return "我也不知道".$arg[0]."是什么呀";
    }

    /**
     * @RequestMapping("/ping")
     */
    public function ping(){}

    /**
     * @RequestMapping("/pong")
     */
    public function pong(){}

    /**
     * @CQNotice(notice_type="group_admin")
     */
    public function onNotice(){

    }
}