<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/12
 * Time: 10:39
 */

namespace cqbot\mods;


use cqbot\CQBot;
use cqbot\utils\CQUtil;

abstract class ModBase
{
    protected $main;
    protected $data;
    protected $cmds;

    public function __construct(CQBot $main, $data, $mod_cmd = false){
        $this->main = $main;
        $this->data = $data;
        $this->cmds = $mod_cmd;
    }

    public function getUser($data = null){
        return CQUtil::getUser($data === null ? $this->data["user_id"] : $data["user_id"]);
    }

    public function getUserId($data = null){ return $data === null ? strval($this->data["user_id"]) : strval($data["user_id"]); }

    public abstract function execute($it);

    public function reply($msg){ $this->main->reply($msg); }

    public function sendPrivateMsg($user, $msg){ $this->main->sendPrivateMsg($user, $msg); }

    public function sendGroupMsg($user, $msg){ $this->main->sendGroupMsg($user, $msg); }

    public function getMessageType(){ return $this->data["message_type"]; }

    public function getCommands(){ return $this->cmds; }
}