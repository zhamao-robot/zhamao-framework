<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/7/17
 * Time: 2:21 PM
 */

class Help extends ModBase
{
    public function __construct(CQBot $main, $data, bool $mod_cmd = false) { parent::__construct($main, $data, $mod_cmd); }

    public function execute($it) {
        switch ($it[0]) {
            case "帮助":
                $msg = "「机器人帮助」";
                $msg .= "\n王境泽动图：用来生成表情包";
                $msg .= "\ntest：测试机器人回复是否正常";
                $msg .= "\n随机数：生成一个随机数";
                $this->reply($msg);
                return true;
            case "如何增加机器人功能":
                $msg = "机器人功能是在框架中src/cqbot/mods/xxx.php文件中编写的。";
                $msg .= "CQBot采用关键词系统，你可以直接像现有源码一样添加case在switch里面，";
                $msg .= "也可以自己新建一个任意名称的Mod名称，例如Entertain.php，你可以在里面编写娱乐功能。";
                $msg .= "你可以直接复制框架中Entertain.php文件的内容进行编辑。";
                $msg .= "你也可以在tasks/Scheduler.php中tick函数里添加自己的定时执行的功能。";
                $msg .= "预先封装好的机器人函数均在CQUtil类中，只需直接使用静态方法调用即可！";
                $msg .= "更多示例功能会逐渐添加到框架中，记得更新哦～";
                $this->reply($msg);
                return true;
        }
        return false;
    }
}