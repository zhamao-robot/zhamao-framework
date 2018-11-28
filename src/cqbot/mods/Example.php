<?php

/**
 * 这是一个示例模块php文件，你可以直接复制此文件中的代码
 * 然后修改其class的名字（注意要和.php文件的文件名相同）
 * 例如，新建一个Mailer模块，则Mailer模块的文件名字为
 * Mailer.php
 * 如果要开启框架的切割函数激活，请在__construct构造函数中
 * 添加一句：$this->split_execute = true;
 * 默认不会执行execute函数
 */
class Example extends ModBase
{
    public function __construct(CQBot $main, $data) {
        parent::__construct($main, $data);
        //$data为CQHTTP插件上报的消息事件数组
        //这里编写你的内容
        $this->split_execute = true;
    }

    /**
     * 分词函数，如果开启分词模式的话将调用此数组。
     * 如果将一句话使用空格、换行和Tab进行分割，用来处理多项参数的功能指令
     * 例如："随机数   1    100" 将被分割成数组$it ["随机数","1","100"]
     * @param $it
     * @return bool
     */
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