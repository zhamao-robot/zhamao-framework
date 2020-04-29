<?php


namespace Module\Example;


use Framework\Console;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\Http\Middleware;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Connection\CQConnection;
use ZM\ModBase;

/**
 * Class Hello
 * @package Module\Example
 * @since 1.0
 */
class Hello extends ModBase
{
    /**
     * 在机器人连接后向终端输出信息
     * @SwooleEventAt("open",rule="connectType:qq")
     * @param $conn
     */
    public function onConnect(CQConnection $conn){
        Console::info("机器人 ".$conn->getQQ()." 已连接！");
    }
    /**
     * 向机器人发送"你好"，即可回复这句话
     * @CQCommand("你好")
     */
    public function hello(){
        return "你好啊，我是由炸毛框架构建的机器人！";
    }

    /**
     * 中间件测试的一个示例函数
     * @RequestMapping("/httpTimer")
     * @Middleware("timer")
     */
    public function timer(){
        return "This page is used as testing TimerMiddleware! Do not use it in production.";
    }

    /**
     * 框架会默认关闭未知的WebSocket链接，因为这个绑定的事件，你可以根据你自己的需求进行修改
     * @SwooleEventAt(type="open",rule="connectType:unknown")
     */
    public function closeUnknownConn(){
        Console::info("Unknown connection , I will close it.");
        $this->connection->close();
    }
}
