<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/5/2
 * Time: 下午2:43
 */

class GroupMember extends User
{
    private $group = 0;
    private $card = "";
    private $join_time = 0;
    private $last_sent_time = 0;
    private $role = "member";
    private $attribute = [];

    public function __construct($qid, Group $group, $data) {
        parent::__construct($qid);
        $this->group = $group;
        $this->card = $data["card"];
        $this->join_time = $data["join_time"];
        $this->last_sent_time = $data["last_sent_time"];
        $this->role = $data["role"];
        $this->attribute = $data;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group {
        return $this->group;
    }

    /**
     * @return mixed
     */
    public function getCard() {
        return $this->card;
    }

    /**
     * @return mixed
     */
    public function getJoinTime() {
        return $this->join_time;
    }

    /**
     * @return mixed
     */
    public function getLastSentTime() {
        return $this->last_sent_time;
    }

    /**
     * 返回角色
     * @return mixed
     */
    public function getRole() {
        return $this->role;
    }

    /**
     * 返回用户是不是群管理员
     * @return bool
     */
    public function isAdmin() {
        return in_array($this->getRole(), ["owner", "admin"]);
    }

    /**
     * @param string $card
     */
    public function setCard(string $card) {
        $this->card = $card;
        $data = [
            "action" => "set_group_card",
            "params" => [
                "group_id" => $this->getGroup()->getGroupId(),
                "user_id" => $this->getId(),
                "card" => $card
            ]
        ];
        CQUtil::sendAPI(CQUtil::getApiConnectionByQQ($this->getGroup()->getSelfId())->fd, $data, []);
    }

    /**
     * @param int $join_time
     */
    public function setJoinTime(int $join_time) {
        $this->join_time = $join_time;
    }

    /**
     * @param int $last_sent_time
     */
    public function setLastSentTime(int $last_sent_time) {
        $this->last_sent_time = $last_sent_time;
    }

    /**
     * @param string $role
     */
    public function setRole(string $role) {
        $this->role = $role;
    }

    /**
     * @return array
     */
    public function getAttribute(): array {
        return $this->attribute;
    }

    /**
     * @param array $attribute
     */
    public function setAttribute(array $attribute) {
        $this->attribute = $attribute;
    }

    /**
     * 更新群组成员信息
     */
    public function updateData() {
        $user_id = $this->getId();
        CQUtil::sendAPI(CQUtil::getApiConnectionByQQ($this->getGroup()->getSelfId())->fd, [
            "action" => "get_group_member_info",
            "params" => [
                "group_id" => $this->getGroup()->getGroupId(),
                "user_id" => $user_id,
                "no_cache" => true
            ]
        ], ["update_group_member_info", $this->getGroup()->getGroupId(), $user_id]);
    }

}