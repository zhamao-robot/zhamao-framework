<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/2
 * Time: 下午1:59
 */

class Group
{
    private $group_id;
    private $group_name;
    private $self_id;
    //private $prefix;
    private $members = [];

    public function __construct($group_id, $info, $self_id) {
        $this->self_id = $self_id;
        $this->group_id = $group_id;
        $this->group_name = $info["group_name"];
        //$this->prefix = $info["prefix"];
        $member_list = $info["member"];
        $this->members = [];
        foreach ($member_list as $k => $v) {
            $this->members[$v["user_id"]] = new GroupMember($v["user_id"], $this, $v);
        }
    }

    /**
     * @return mixed
     */
    public function getGroupId() {
        return $this->group_id;
    }

    /**
     * @return mixed
     */
    public function getGroupName() {
        return $this->group_name;
    }

    /**
     * @return array
     */
    public function getMembers(): array {
        return $this->members;
    }

    /**
     * @param $user_id
     * @return GroupMember|null
     */
    public function getMember($user_id) {
        return isset($this->members[$user_id]) ? $this->members[$user_id] : null;
    }

    /**
     * @param mixed $group_name
     */
    public function setGroupName($group_name) {
        $this->group_name = $group_name;
    }

    /**
     * set群成员的类
     * @param array $members
     */
    public function setMembers(array $members) {
        $this->members = $members;
    }

    /**
     * set群成员的类
     * @param $user_id
     * @param GroupMember $member
     */
    public function setMember($user_id, GroupMember $member) {
        $this->members[$user_id] = $member;
    }

    /**
     * 更新群信息
     * @param bool $with_members
     */
    public function updateData($with_members = false) {
        $connection = CQUtil::getApiConnectionByQQ($this->getSelfId());
        CQUtil::sendAPI($connection->fd, ["action" => "get_group_list"], ["update_group_info", $this->getGroupId()]);
        if ($with_members) {
            CQUtil::sendAPI($connection->fd, ["action" => "get_group_member_list", "params" => ["group_id" => $this->getGroupId()]], ["update_group_member_list", strval($this->getGroupId())]);
        }
    }

    /**
     * @return mixed
     */
    public function getSelfId() {
        return $this->self_id;
    }
}