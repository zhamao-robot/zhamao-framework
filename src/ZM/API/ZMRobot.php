<?php /** @noinspection PhpUnused */


namespace ZM\API;

use ZM\ConnectionManager\ConnectionObject;
use ZM\ConnectionManager\ManagerGM;
use ZM\Exception\RobotNotFoundException;

/**
 * Class ZMRobot
 * @package ZM\Utils
 * @since 1.2
 */
class ZMRobot
{
    use CQAPI;
    const API_ASYNC = 1;
    const API_NORMAL = 0;
    const API_RATE_LIMITED = 2;

    /** @var ConnectionObject|null */
    private $connection;

    private $callback = null;
    private $prefix = 0;

    /**
     * @param $robot_id
     * @return ZMRobot
     * @throws RobotNotFoundException
     */
    public static function get($robot_id) {
        $r = ManagerGM::getAllByName('qq');
        foreach($r as $v) {
            if($v->getOption('connect_id') == $robot_id) return new ZMRobot($v);
        }
        throw new RobotNotFoundException("机器人 " . $robot_id . " 未连接到框架！");
    }

    /**
     * @throws RobotNotFoundException
     * @return ZMRobot
     */
    public static function getRandom() {
        $r = ManagerGM::getAllByName('qq');
        if($r == []) throw new RobotNotFoundException("没有任何机器人连接到框架！");
        return new ZMRobot($r[array_rand($r)]);
    }

    public function __construct(ConnectionObject $connection) {
        $this->connection = $connection;
    }

    public function setCallback($callback = true) {
        $this->callback = $callback;
        return $this;
    }

    public function setPrefix($prefix = self::API_NORMAL) {
        $this->prefix = $prefix;
        return $this;
    }

    public function sendPrivateMsg($user_id, $message, $auto_escape = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'user_id' => $user_id,
                'message' => $message,
                'auto_escape' => $auto_escape
            ]
        ], $this->callback);
    }

    public function sendGroupMsg($group_id, $message, $auto_escape = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'message' => $message,
                'auto_escape' => $auto_escape
            ]
        ], $this->callback);
    }

    public function sendDiscussMsg($discuss_id, $message, $auto_escape = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'discuss_id' => $discuss_id,
                'message' => $message,
                'auto_escape' => $auto_escape
            ]
        ], $this->callback);
    }

    public function sendMsg($message_type, $target_id, $message, $auto_escape = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'message_type' => $message_type,
                ($message_type == 'private' ? 'user' : $message_type) . '_id' => $target_id,
                'message' => $message,
                'auto_escape' => $auto_escape
            ]
        ], $this->callback);
    }

    public function deleteMsg($message_id) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'message_id' => $message_id
            ]
        ], $this->callback);
    }

    public function sendLike($user_id, $times = 1) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'user_id' => $user_id,
                'times' => $times
            ]
        ], $this->callback);
    }

    public function setGroupKick($group_id, $user_id, $reject_add_request = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'user_id' => $user_id,
                'reject_add_request' => $reject_add_request
            ]
        ], $this->callback);
    }

    public function setGroupBan($group_id, $user_id, $duration = 1800) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'user_id' => $user_id,
                'duration' => $duration
            ]
        ], $this->callback);
    }

    public function setGroupAnonymousBan($group_id, $anonymous_or_flag, $duration = 1800) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                (is_string($anonymous_or_flag) ? 'flag' : 'anonymous') => $anonymous_or_flag,
                'duration' => $duration
            ]
        ], $this->callback);
    }

    public function setGroupWholeBan($group_id, $enable = true) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'enable' => $enable
            ]
        ], $this->callback);
    }

    public function setGroupAdmin($group_id, $user_id, $enable = true) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'user_id' => $user_id,
                'enable' => $enable
            ]
        ], $this->callback);
    }

    public function setGroupAnonymous($group_id, $enable = true) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'enable' => $enable
            ]
        ], $this->callback);
    }

    public function setGroupCard($group_id, $user_id, $card = "") {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'user_id' => $user_id,
                'card' => $card
            ]
        ], $this->callback);
    }

    public function setGroupLeave($group_id, $is_dismiss = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'is_dismiss' => $is_dismiss
            ]
        ], $this->callback);
    }

    public function setGroupSpecialTitle($group_id, $user_id, $special_title = "", $duration = -1) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'user_id' => $user_id,
                'special_title' => $special_title,
                'duration' => $duration
            ]
        ], $this->callback);
    }

    public function setDiscussLeave($discuss_id) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'discuss_id' => $discuss_id
            ]
        ], $this->callback);
    }

    public function setFriendAddRequest($flag, $approve = true, $remark = "") {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'flag' => $flag,
                'approve' => $approve,
                'remark' => $remark
            ]
        ], $this->callback);
    }

    public function setGroupAddRequest($flag, $sub_type, $approve = true, $reason = "") {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'flag' => $flag,
                'sub_type' => $sub_type,
                'approve' => $approve,
                'reason' => $reason
            ]
        ], $this->callback);
    }

    public function getLoginInfo() {
        return $this->processAPI($this->connection, ['action' => $this->getActionName(__FUNCTION__)], $this->callback);
    }

    public function getStrangerInfo($user_id, $no_cache = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'user_id' => $user_id,
                'no_cache' => $no_cache
            ]
        ], $this->callback);
    }

    public function getFriendList() {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__)
        ], $this->callback);
    }

    public function getGroupList() {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__)
        ], $this->callback);
    }

    public function getGroupInfo($group_id, $no_cache = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'no_cache' => $no_cache
            ]
        ], $this->callback);
    }

    public function getGroupMemberInfo($group_id, $user_id, $no_cache = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'user_id' => $user_id,
                'no_cache' => $no_cache
            ]
        ], $this->callback);
    }

    public function getGroupMemberList($group_id) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id
            ]
        ]);
    }

    public function getCookies($domain = "") {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'domain' => $domain
            ]
        ]);
    }

    public function getCsrfToken() {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__)
        ], $this->callback);
    }

    public function getCredentials($domain = "") {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'domain' => $domain
            ]
        ], $this->callback);
    }

    public function getRecord($file, $out_format, $full_path = false) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'file' => $file,
                'out_format' => $out_format,
                'full_path' => $full_path
            ]
        ], $this->callback);
    }

    public function getImage($file) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'file' => $file
            ]
        ], $this->callback);
    }

    public function canSendImage() {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__)
        ], $this->callback);
    }

    public function canSendRecord() {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__)
        ], $this->callback);
    }

    public function getStatus() {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__)
        ], $this->callback);
    }

    public function getVersionInfo() {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__)
        ], $this->callback);
    }

    public function setRestartPlugin($delay = 0) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'delay' => $delay
            ]
        ], $this->callback);
    }

    public function cleanDataDir($data_dir) {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__),
            'params' => [
                'data_dir' => $data_dir
            ]
        ], $this->callback);
    }

    public function cleanPluginLog() {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName(__FUNCTION__)
        ], $this->callback);
    }

    public function getExperimentAPI() {
        return new ZMRobotExperiment($this->connection, $this->callback, $this->prefix);
    }

    private function getActionName(string $method) {
        $prefix = ($this->prefix == self::API_ASYNC ? '_async' : ($this->prefix == self::API_RATE_LIMITED ? '_rate_limited' : ''));
        $func_name = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $method));
        return $func_name . $prefix;
    }
}
