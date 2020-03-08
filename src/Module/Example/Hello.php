<?php


namespace Module\Example;


use Framework\Console;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\Http\Controller;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Connection\CQConnection;
use ZM\ModBase;

/**
 * Class Hello
 * @package Module\Example
 * @Controller("/view")
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

    /**
     * @RequestMapping("/test/{ping}")
     */
    public function ping($param){
        return "You input id is: ".$param["ping"];
    }

    /**
     * @RequestMapping("/test/pong")
     */
    public function pong(){
        $this->response->end("ping");
    }

    /**
     * @SwooleEventAt(type="open",rule="connectType:unknown")
     */
    public function closeUnknownConn(){
        Console::info("Unknown connection , I will close it.");
        $this->connection->close();
    }
}