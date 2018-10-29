<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/4/14
 * Time: 13:29
 */

use DataProvider as DP;

class User
{
    private $id;

    protected $nickname = "";
    protected $permission = 0;
    protected $friend = [];
    protected $lexicon = [];
    protected $user_type = 0;//预留位置，0为QQ用户，1为为微信公众号用户

    protected $function = [];

    public $buffer = null;

    public function __construct($qid) {
        $this->id = $qid;
        if (strlen($qid) >= 15) $this->user_type = 1;
        $this->permission = DP::getJsonData("permissions.json")[$qid] ?? 0;
        $this->friend = [];
    }

    /**
     * 获取用户QQ号
     * @return mixed
     */
    public function getId() { return $this->id; }

    /**
     * 获取用户权限值
     * @return int
     */
    public function getPermission() { return $this->permission; }

    /**
     * @param int $permission
     * @return User
     */
    public function setPermission(int $permission) {
        $this->permission = $permission;
        return $this;
    }

    public function getBuffer() {
        return $this->buffer;
    }

    public function setBuffer($buffer) {
        $this->buffer = $buffer;
        return $this;
    }

    /**
     * @return string
     */
    public function getNickname() {
        return $this->nickname;
    }

    /**
     * @return array
     */
    public function getFriend() {
        return $this->friend;
    }

    /**
     * @param array $friend
     */
    public function setFriend(array $friend) {
        $this->friend = $friend;
    }

    /**
     * @param string $nickname
     */
    public function setNickname(string $nickname) {
        $this->nickname = $nickname;
    }

    /**
     * @return array
     */
    public function getLexicon() {
        return $this->lexicon;
    }

    /**
     * @param array $lexicon
     */
    public function setLexicon(array $lexicon) {
        $this->lexicon = $lexicon;
    }

    /**
     * @return array
     */
    public function getFunction() {
        return $this->function;
    }

    /**
     * @param array $function
     */
    public function setFunction(array $function) {
        $this->function = $function;
    }

    public function closeFunction($name) {
        unset($this->function[$name]);
    }

    /**
     * @return int
     */
    public function getUserType() {
        return $this->user_type;
    }

    public function toJson() {
        $ls = [];
        foreach ($this as $k => $v) {
            $ls[$k] = $v;
        }
        return json_encode($ls, 128 | 256);
    }
}