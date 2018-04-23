<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/14
 * Time: 13:29
 */

namespace cqbot;

use cqbot\utils\Buffer;
use cqbot\utils\DataProvider as DP;

class User
{
    private $id;
    private $word_status = [];
    private $permission;
    private $is_friend = false;

    private $buffer = null;

    public function __construct($qid){
        $this->id = $qid;
        $this->permission = DP::getJsonData("permissions.json")[$qid] ?? 0;
        $this->is_friend = isset(Buffer::get("friend_list")[$qid]) ? true : false;
    }

    /**
     * 获取用户QQ号
     * @return mixed
     */
    public function getId(){ return $this->id; }

    /**
     * 获取用户添加词库的状态
     * @return array
     */
    public function getWordStatus() : array{ return $this->word_status; }

    /**
     * 获取用户权限值
     * @return int
     */
    public function getPermission() : int{ return $this->permission; }

    /**
     * @param array $word_status
     * @return User
     */
    public function setWordStatus(array $word_status): User{
        $this->word_status = $word_status;
        return $this;
    }

    /**
     * @param int $permission
     * @return User
     */
    public function setPermission(int $permission): User{
        $this->permission = $permission;
        return $this;
    }

    /**
     * 和用户是否是好友//TODO功能
     * @return bool
     */
    public function isFriend(): bool{
        return $this->is_friend;
    }

    public function getBuffer(){
        return $this->buffer;
    }

    public function setBuffer($buffer){
        $this->buffer = $buffer;
        return $this;
    }
}