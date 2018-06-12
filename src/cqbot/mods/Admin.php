<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/19
 * Time: 14:55
 */

class Admin extends ModBase
{
    protected $cmds;

    public function __construct(CQBot $main, $data){
        parent::__construct($main, $data);
    }

    public function execute($it){
        if (!$this->main->isAdmin($this->getUserId())) return false;
        switch ($it[0]) {
            case "reload":
                $this->reply("正在重新启动...");
                if (file_get_contents("/home/ubuntu/CrazyBotFramework/src/Framework.php") != Buffer::get("res_code"))
                    $this->reply("检测到改变了Framework文件的内容！如需完全重载，请重启完整进程！");
                CQUtil::reload();
                return true;
            case "stop":
                $this->reply("正在停止服务器...");
                CQUtil::stop();
                return true;
            case "set-prefix":
                if(count($it) < 2) return $this->sendDefaultHelp($it[0],["set-prefix","新前缀/空"],"设置新的前缀或设置不需要前缀（不需要前缀输入\"空\"即可）");
                $prefix = $it[1];
                if(mb_strlen($prefix) > 2){
                    $this->reply("指令前缀最长为两个字符");
                    return true;
                }
                Buffer::set("cmd_prefix", $prefix);
                $this->reply("成功设置新前缀！\n下次输入指令时需前面带 ".$prefix);
                return true;
            case "op":
                $user = $it[1];
                Buffer::append("su", $user);
                $this->reply("added operator $user");
                return true;
            case "deop":
                $user = $it[1];
                if(Buffer::in_array("su", $user)) Buffer::unsetByValue("su", $user);
                $this->reply("removed operator $user");
                return true;
        }
        return false;
    }
}