<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/12
 * Time: 10:39
 */

/**
 * Class ModBase
 * @method static onRequest($req)
 * @method static onNotice($req)
 */
abstract class ModBase
{
    protected $main;
    protected $data;

    /**
     * 控制模块是否调用分割函数的变量
     * 当split 为FALSE时，表明CQBot主实例不需要调用execute函数
     * 当为TRUE时，CQBot在实例化模块对象后会执行execute函数
     * @var bool
     */
    public $split_execute = false;

    public function __construct(CQBot $main, $data) {
        $this->main = $main;
        $this->data = $data;
    }

    public function execute($it) { }

    public function getUser($data = null) { return CQUtil::getUser($data === null ? $this->data["user_id"] : $data["user_id"]); }

    public function getUserId($data = null) { return $data === null ? strval($this->data["user_id"]) : strval($data["user_id"]); }

    public function reply($msg, callable $callback = null) { return $this->main->reply($msg, $callback); }

    public function getMessageType() { return $this->data["message_type"]; }

    public function getRobotId() { return $this->data["self_id"]; }
}