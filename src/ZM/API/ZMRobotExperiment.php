<?php


namespace ZM\API;


use ZM\ConnectionManager\ConnectionObject;

/**
 * Class ZMRobotExperiment
 * @package ZM\Utils
 * @since 1.2
 */
class ZMRobotExperiment
{
    use CQAPI;

    private $connection;

    private $callback = null;
    private $prefix = 0;

    public function __construct(ConnectionObject $connection, $callback, $prefix) {
        $this->connection = $connection;
        $this->callback = $callback;
        $this->prefix = $prefix;
    }

    public function setCallback($callback = true) {
        $this->callback = $callback;
        return $this;
    }

    public function setPrefix($prefix = ZMRobot::API_NORMAL) {
        $this->prefix = $prefix;
        return $this;
    }

    public function getFriendList($flat = false) {
        return $this->processAPI($this->connection, [
            'action' => '_' . $this->getActionName(__FUNCTION__),
            'params' => [
                'flat' => $flat
            ]
        ], $this->callback);
    }

    public function getGroupInfo($group_id) {
        return $this->processAPI($this->connection, [
            'action' => '_' . $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id
            ]
        ], $this->callback);
    }

    public function getVipInfo($user_id) {
        return $this->processAPI($this->connection, [
            'action' => '_' . $this->getActionName(__FUNCTION__),
            'params' => [
                'user_id' => $user_id
            ]
        ], $this->callback);
    }

    public function getGroupNotice($group_id) {
        return $this->processAPI($this->connection, [
            'action' => '_' . $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id
            ]
        ], $this->callback);
    }

    public function sendGroupNotice($group_id, $title, $content) {
        return $this->processAPI($this->connection, [
            'action' => '_' . $this->getActionName(__FUNCTION__),
            'params' => [
                'group_id' => $group_id,
                'title' => $title,
                'content' => $content
            ]
        ], $this->callback);
    }

    public function setRestart($clean_log = false, $clean_cache = false, $clean_event = false) {
        return $this->processAPI($this->connection, [
            'action' => '_' . $this->getActionName(__FUNCTION__),
            'params' => [
                'clean_log' => $clean_log,
                'clean_cache' => $clean_cache,
                'clean_event' => $clean_event
            ]
        ], $this->callback);
    }

    private function getActionName(string $method) {
        $prefix = ($this->prefix == ZMRobot::API_ASYNC ? '_async' : ($this->prefix == ZMRobot::API_RATE_LIMITED ? '_rate_limited' : ''));
        $func_name = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $method));
        return $prefix . $func_name;
    }
}
