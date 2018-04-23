<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/19
 * Time: 14:55
 */

namespace cqbot\mods;


use cqbot\CQBot;
use cqbot\utils\CQUtil;

class Admin extends ModBase
{
    protected $cmds;

    public function __construct(CQBot $main, $data){
        //这里放置你的本模块的新加的指令，写入后系统会自动添加到指令列表
        $cmds = [
            "add-cmd"
        ];
        parent::__construct($main, $data, $cmds);
    }

    public function execute($it){
        if (!$this->main->isAdmin($this->getUserId())) return false;
        switch ($it[0]) {
            case "add-cmd":
                if (count($it) < 3) {
                    $this->reply("用法：add-cmd 指令 模块名");
                    return true;
                }
                if(!CQUtil::isModExists($it[2])){
                    $this->reply("对不起，模块 ".$it[2]." 不存在！");
                    return true;
                }
        }
    }
}