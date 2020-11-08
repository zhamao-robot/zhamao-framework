<?php

namespace Module\Example;

use ZM\Annotation\Http\Middleware;
use ZM\Annotation\Swoole\OnSwooleEvent;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Console\Console;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\Http\RequestMapping;
use ZM\Store\Redis\ZMRedis;

/**
 * Class Hello
 * @package Module\Example
 * @since 2.0
 */
class Hello
{
    /**
     * 一个简单的redis连接池使用demo，将下方user_id改为你自己的QQ号即可(为了不被不法分子利用)
     * @CQCommand("redis_test",user_id=627577391)
     */
    public function testCase() {
        $a = new ZMRedis();
        $redis = $a->get();
        $r1 = ctx()->getArgs(ZM_MATCH_FIRST, "请说出你想设置的操作[r/w]");
        switch ($r1) {
            case "r":
                $k = ctx()->getArgs(ZM_MATCH_FIRST, "请说出你想读取的键名");
                $result = $redis->get($k);
                ctx()->reply("结果：" . $result);
                break;
            case "w":
                $k = ctx()->getArgs(ZM_MATCH_FIRST, "请说出你想写入的键名");
                $v = ctx()->getArgs(ZM_MATCH_FIRST, "请说出你想写入的字符串");
                $result = $redis->set($k, $v);
                ctx()->reply("结果：" . ($result ? "成功" : "失败"));
                break;
        }
    }

    /**
     * @CQCommand("我是谁")
     */
    public function whoami() {
        $user = ctx()->getRobot()->setCallback(true)->getLoginInfo();
        return "你是" . $user["data"]["nickname"] . "，QQ号是" . $user["data"]["user_id"];
    }

    /**
     * 向机器人发送"你好啊"，也可回复这句话
     * @CQCommand(match="你好",alias={"你好啊","你是谁"})
     */
    public function hello() {
        return "你好啊，我是由炸毛框架构建的机器人！";
    }

    /**
     * 一个简单随机数的功能demo
     * 问法1：随机数 1 20
     * 问法2：从1到20的随机数
     * @CQCommand("随机数")
     * @CQCommand(pattern="*从*到*的随机数")
     * @return string
     */
    public function randNum() {
        // 获取第一个数字类型的参数
        $num1 = ctx()->getArgs(ZM_MATCH_NUMBER, "请输入第一个数字");
        // 获取第二个数字类型的参数
        $num2 = ctx()->getArgs(ZM_MATCH_NUMBER, "请输入第二个数字");
        $a = min(intval($num1), intval($num2));
        $b = max(intval($num1), intval($num2));
        // 回复用户结果
        return "随机数是：" . mt_rand($a, $b);
    }

    /**
     * 中间件测试的一个示例函数
     * @RequestMapping("/httpTimer")
     * @Middleware("timer")
     */
    public function timer() {
        return "This page is used as testing TimerMiddleware! Do not use it in production.";
    }

    /**
     * 默认示例页面
     * @RequestMapping("/index")
     * @RequestMapping("/")
     */
    public function index() {
        return "Hello Zhamao!";
    }

    /**
     * 使用自定义参数的路由参数
     * @RequestMapping("/whoami/{name}")
     * @param $param
     * @return string
     */
    public function paramGet($param) {
        return "Your name: {$param["name"]}";
    }

    /**
     * 在机器人连接后向终端输出信息
     * @OnSwooleEvent("open",rule="connectIsQQ()")
     * @param $conn
     */
    public function onConnect(ConnectionObject $conn) {
        Console::info("机器人 " . $conn->getOption("connect_id") . " 已连接！");
    }

    /**
     * 在机器人断开连接后向终端输出信息
     * @OnSwooleEvent("close",rule="connectIsQQ()")
     * @param ConnectionObject $conn
     */
    public function onDisconnect(ConnectionObject $conn) {
        Console::info("机器人 " . $conn->getOption("connect_id") . " 已断开连接！");
    }

    /**
     * 框架会默认关闭未知的WebSocket链接，因为这个绑定的事件，你可以根据你自己的需求进行修改
     * @OnSwooleEvent(type="open",rule="connectIsDefault()")
     */
    public function closeUnknownConn() {
        Console::info("Unknown connection , I will close it.");
        server()->close(ctx()->getConnection()->getFd());
    }
}
