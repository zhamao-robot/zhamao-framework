<?php

/**
 * 这是一个示例模块php文件，你可以直接复制此文件中的代码
 * 然后修改其class的名字（注意要和.php文件的文件名相同）
 * 例如，新建一个Mailer模块，则Mailer模块的文件名字为
 * Mailer.php
 * 然后更改class Entertain为class Mailer即可
 * 功能在execute中编写即可
 * 如果需要写判断所有文本的功能，则直接在__construct的parent::__construct下方编写自己的内容即可
 */
class Example extends ModBase
{
    protected $cmds;

    public function __construct(CQBot $main, $data) {
        parent::__construct($main, $data);
        $message = $data["message"];

        //这里可以写一些匹配所有文本的功能，例如下面的一个简单的debug
        if (mb_substr($message, 0, 3) == "dbg") {
            $this->reply("你输入了：" . mb_substr($message, 3));
        }
    }

    public function execute($it) {
        switch ($it[0]) {
            case "ping":
                $this->reply("pong");
                return true;
            case "你好":
                $this->reply("你好，我是CQBot！");
                return true;
            case "随机数":
                if (!isset($it[1]) || !isset($it[2])) {
                    $this->reply("用法： 随机数 开始整数 结束整数");
                    return true;
                }
                $c1 = intval($it[1]);
                $c2 = intval($it[2]);
                if ($c1 > $c2) {
                    $this->reply("随机数范围错误！应该从小的一方到大的一方！例如：\n随机数 1 99");
                    return true;
                }
                $this->reply("生成的随机数是 " . mt_rand($c1, $c2));
                return true;
        }
        return false;
    }
}