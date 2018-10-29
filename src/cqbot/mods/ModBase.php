<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/12
 * Time: 10:39
 */

abstract class ModBase
{
    protected $main;
    protected $data;
    public $call_task = false;

    /**
     * 控制模块是否调用execute函数的变量
     * 当function_call 为 TRUE时，表明CQBot主实例不需要调用execute函数
     * 当为FALSE时，CQBot在实例化模块对象后会执行execute函数
     * @var bool
     */
    public $function_call = false;

    public function __construct(CQBot $main, $data) {
        $this->main = $main;
        $this->data = $data;
    }

    public function getUser($data = null) {
        return CQUtil::getUser($data === null ? $this->data["user_id"] : $data["user_id"]);
    }

    public function getUserId($data = null) { return $data === null ? strval($this->data["user_id"]) : strval($data["user_id"]); }

    public function execute($it) { }

    public function reply($msg) { $this->main->reply($msg); }

    public function sendPrivateMsg($user, $msg) { $this->main->sendPrivateMsg($user, $msg); }

    public function sendGroupMsg($user, $msg) { $this->main->sendGroupMsg($user, $msg); }

    public function getMessageType() { return $this->data["message_type"]; }

    public function getRobotId() { return $this->data["self_id"]; }
}