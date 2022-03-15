<?php

declare(strict_types=1);

namespace Module\Example;

use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQCommand;
use ZM\Annotation\CQ\CQMessage;
use ZM\Annotation\Http\Middleware;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Swoole\OnCloseEvent;
use ZM\Annotation\Swoole\OnOpenEvent;
use ZM\Annotation\Swoole\OnRequestEvent;
use ZM\API\CQ;
use ZM\API\OneBotV11;
use ZM\API\TuringAPI;
use ZM\ConnectionManager\ConnectionObject;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Exception\InterruptException;
use ZM\Module\QQBot;
use ZM\Requests\ZMRequest;
use ZM\Utils\MessageUtil;
use ZM\Utils\ZMUtil;

/**
 * Class Hello
 * @since 2.0
 */
class Hello
{
    /*
     * 默认的图片监听路由对应目录，如需要使用可取消下面的注释，把上面的 /* 换成 /**
     * @OnStart(-1)
     */
    //public function onStart() {
    //    \ZM\Http\RouteManager::addStaticFileRoute("/images/", \ZM\Utils\DataProvider::getWorkingDir()."/zm_data/images/");
    //}

    /**
     * 使用命令 .reload 发给机器人远程重载，注意将 user_id 换成你自己的 QQ
     * @CQCommand(".reload",user_id=627577391)
     */
    public function reload()
    {
        ctx()->reply('重启中...');
        ZMUtil::reload();
    }

    /**
     * @CQCommand("我是谁")
     */
    public function whoami()
    {
        $bot = ctx()->getRobot()->getLoginInfo();
        $bot_id = $bot['data']['user_id'];
        $r = OneBotV11::get($bot_id);
        $QQid = ctx()->getUserId();
        $nick = $r->getStrangerInfo($QQid)['data']['nickname'];
        return '你是' . $nick . '，QQ号是' . $QQid;
    }

    /**
     * 向机器人发送"你好啊"，也可回复这句话
     * @CQCommand(match="你好",alias={"你好啊","你是谁"})
     */
    public function hello()
    {
        return '你好啊，我是由炸毛框架构建的机器人！';
    }

    /**
     * 一个最基本的第三方 API 接口使用示例
     * @CQCommand("一言")
     */
    public function hitokoto()
    {
        $api_result = ZMRequest::get('https://v1.hitokoto.cn/');
        if ($api_result === false) {
            return '接口请求出错，请稍后再试！';
        }
        $obj = json_decode($api_result, true);
        if ($obj === null) {
            return '接口解析出错！可能返回了非法数据！';
        }
        return $obj['hitokoto'] . "\n----「" . $obj['from'] . '」';
    }

    /**
     * 图灵机器人的内置实现，在tuling123.com申请一个apikey填入下方变量即可。
     * @CQCommand(start_with="机器人",end_with="机器人",message_type="group")
     * @CQMessage(message_type="private",level=1)
     */
    public function turingAPI()
    {
        $user_id = ctx()->getUserId();
        $api = ''; // 请在这里填入你的图灵机器人的apikey
        if ($api === '') {
            return false;
        } //如果没有填入apikey则此功能关闭
        if (($this->_running_annotation ?? null) instanceof CQCommand) {
            $msg = ctx()->getFullArg('我在！有什么事吗？');
        } else {
            $msg = ctx()->getMessage();
        }
        ctx()->setMessage($msg);
        if (MessageUtil::matchCommand($msg, ctx()->getData())->status === false) {
            return TuringAPI::getTuringMsg($msg, $user_id, $api);
        }
        QQBot::getInstance()->handle(ctx()->getData(), ctx()->getCache('level') + 1);
        //执行嵌套消息，递归层级+1
        return true;
    }

    /**
     * 响应at机器人的消息
     * @CQBefore("message")
     */
    public function changeAt()
    {
        if (MessageUtil::isAtMe(ctx()->getMessage(), ctx()->getRobotId())) {
            $msg = str_replace(CQ::at(ctx()->getRobotId()), '', ctx()->getMessage());
            ctx()->setMessage('机器人' . $msg);
            Console::info(ctx()->getMessage());
        }
        return true;
    }

    /**
     * 一个简单随机数的功能demo
     * 问法1：随机数 1 20
     * 问法2：从1到20的随机数
     * @CQCommand("随机数")
     * @CQCommand(pattern="*从*到*的随机数")
     * @return string
     */
    public function randNum()
    {
        // 获取第一个数字类型的参数
        $num1 = ctx()->getNumArg('请输入第一个数字');
        // 获取第二个数字类型的参数
        $num2 = ctx()->getNumArg('请输入第二个数字');
        $a = min(intval($num1), intval($num2));
        $b = max(intval($num1), intval($num2));
        // 回复用户结果
        return '随机数是：' . mt_rand($a, $b);
    }

    /**
     * 中间件测试的一个示例函数
     * @RequestMapping("/httpTimer")
     * @Middleware("timer")
     */
    public function timer()
    {
        return 'This page is used as testing TimerMiddleware! Do not use it in production.';
    }

    /**
     * 默认示例页面
     * @RequestMapping("/index")
     * @RequestMapping("/")
     */
    public function index()
    {
        return 'Hello Zhamao!';
    }

    /**
     * 使用自定义参数的路由参数
     * @RequestMapping("/whoami/{name}")
     * @param $param
     * @return string
     */
    public function paramGet($param)
    {
        return 'Hello, ' . $param['name'];
    }

    /**
     * 在机器人连接后向终端输出信息
     * @OnOpenEvent("qq")
     * @param $conn
     */
    public function onConnect(ConnectionObject $conn)
    {
        Console::info('机器人 ' . $conn->getOption('connect_id') . ' 已连接！');
    }

    /**
     * 在机器人断开连接后向终端输出信息
     * @OnCloseEvent("qq")
     */
    public function onDisconnect(ConnectionObject $conn)
    {
        Console::info('机器人 ' . $conn->getOption('connect_id') . ' 已断开连接！');
    }

    /**
     * 阻止 Chrome 自动请求 /favicon.ico 导致的多条请求并发和干扰
     * @OnRequestEvent(rule="ctx()->getRequest()->server['request_uri'] == '/favicon.ico'",level=200)
     * @throws InterruptException
     */
    public function onRequest()
    {
        EventDispatcher::interrupt();
    }

    /**
     * 框架会默认关闭未知的WebSocket链接，因为这个绑定的事件，你可以根据你自己的需求进行修改
     * @OnOpenEvent("default")
     */
    public function closeUnknownConn()
    {
        Console::info('Unknown connection , I will close it.');
        server()->disconnect(ctx()->getConnection()->getFd());
    }
}
