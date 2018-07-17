<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/6/3
 * Time: 下午3:12
 */

class Example extends ModBase
{
    protected $cmds;

    public function __construct(CQBot $main, $data){
        parent::__construct($main, $data);
    }

    public function execute($it){
        switch ($it[0]) {
            case "ping":
                $this->reply("pong");
                return true;
            case "王境泽动图":
                $msg_help = "【王境泽动图帮助】";
                $msg_help .= "\n用法1：\n王境泽动图 第一句 第二句 第三句 第四句";
                $msg_help .= "\n[如果需要输入空格的话，用下面的方法]";
                $msg_help .= "\n王境泽动图 多行\n第一句\n第二句\n第三句\n第四句";
                $api = "https://sorry.xuty.tk/api/wangjingze/make";
                if (strstr($it[1], "\n") === false && count($it) < 5) {
                    $this->reply($msg_help);
                    return true;
                }
                array_shift($it);
                if (mb_substr($it[0], 0, 2) == "多行") {
                    $ms = implode(" ", $it);
                    $ms = mb_substr($ms, 2);
                    $ms = trim($ms);
                    $ms = explode("\n", $ms);
                    if (count($ms) < 4) {
                        $this->reply($msg_help);
                        return true;
                    }
                    $content = [
                        "3" => $ms[3],
                        "2" => $ms[2],
                        "1" => $ms[1],
                        "0" => $ms[0]
                    ];
                } elseif (count($it) >= 4) {
                    $content = [
                        "3" => $it[3],
                        "0" => $it[0],
                        "1" => $it[1],
                        "2" => $it[2]
                    ];
                } else {
                    $this->reply($msg_help);
                    return true;
                }
                $opts = array('http' => array('method' => 'POST', 'header' => 'Content-Type: application/json; charset=utf-8', 'content' => json_encode($content, JSON_UNESCAPED_UNICODE)));
                $context = stream_context_create($opts);
                $this->reply("正在生成，请稍等");
                $result = file_get_contents($api, false, $context);
                if ($result == false) {
                    $this->reply("抱歉，请求失败，请过一会儿再试吧～");
                    return true;
                }
                $result = "https://sorry.xuty.tk" . $result;
                $this->reply("[CQ:image,file=" . $result . "]");
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
            case "test":
                $this->reply("Hello world");
                return true;
        }
        return false;
    }
}