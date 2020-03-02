<?php


namespace Module\Example;


use Framework\Console;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\Module\Closed;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Connection\CQConnection;
use ZM\ModBase;

/**
 * Class Hello
 * @package Module\Example
 * @Closed()
 */
class Hello extends ModBase
{
    /**
     * @SwooleEventAt("open",rule="connectType:qq")
     * @param $conn
     */
    public function onConnect(CQConnection $conn){
        Console::info("机器人 ".$conn->getQQ()." 已连接！");
    }
    /**
     * @CQCommand("你好")
     */
    public function hello(){
        return "你好啊，我是由炸毛框架构建的机器人！";
    }
}