<?php

declare(strict_types=1);

namespace ZM\API;

class GoCqhttpAPIV11
{
    use CQAPI;

    public const SUPPORT_VERSION = '1.0.0-beta8';

    private $connection;

    private $callback;

    private $prefix;

    public function __construct($connection, $callback, $prefix)
    {
        $this->connection = $connection;
        $this->callback = $callback;
        $this->prefix = $prefix;
    }

    /**
     * 获取频道系统内BOT的资料
     * 响应字段：nickname, tiny_id, avatar_url
     * @see https://github.com/Mrs4s/go-cqhttp/blob/master/docs/guild.md#%E8%8E%B7%E5%8F%96%E9%A2%91%E9%81%93%E7%B3%BB%E7%BB%9F%E5%86%85bot%E7%9A%84%E8%B5%84%E6%96%99
     * @return array|bool 返回API调用结果（数组）或异步API调用状态（bool）
     */
    public function getGuildServiceProfile()
    {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName($this->prefix, __FUNCTION__),
        ], $this->callback);
    }

    /**
     * 获取频道列表
     * @see https://github.com/Mrs4s/go-cqhttp/blob/master/docs/guild.md#%E8%8E%B7%E5%8F%96%E9%A2%91%E9%81%93%E5%88%97%E8%A1%A8
     * @return array|bool 返回API调用结果（数组）或异步API调用状态（bool）
     */
    public function getGuildList()
    {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName($this->prefix, __FUNCTION__),
        ], $this->callback);
    }

    /**
     * 通过访客获取频道元数据
     * @see https://github.com/Mrs4s/go-cqhttp/blob/master/docs/guild.md#%E9%80%9A%E8%BF%87%E8%AE%BF%E5%AE%A2%E8%8E%B7%E5%8F%96%E9%A2%91%E9%81%93%E5%85%83%E6%95%B0%E6%8D%AE
     * @param  int|string $guild_id 频道ID
     * @return array|bool 返回API调用结果（数组）或异步API调用状态（bool）
     */
    public function getGuildMetaByGuest($guild_id)
    {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName($this->prefix, __FUNCTION__),
            'params' => [
                'guild_id' => $guild_id,
            ],
        ], $this->callback);
    }

    /**
     * 获取子频道列表
     * @see https://github.com/Mrs4s/go-cqhttp/blob/master/docs/guild.md#%E8%8E%B7%E5%8F%96%E5%AD%90%E9%A2%91%E9%81%93%E5%88%97%E8%A1%A8
     * @param  int|string $guild_id 频道ID
     * @param  false      $no_cache 禁用缓存（默认为false）
     * @return array|bool 返回API调用结果（数组）或异步API调用状态（bool）
     */
    public function getGuildChannelList($guild_id, bool $no_cache = false)
    {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName($this->prefix, __FUNCTION__),
            'params' => [
                'guild_id' => $guild_id,
                'no_cache' => $no_cache,
            ],
        ], $this->callback);
    }

    /**
     * 获取频道成员列表
     * @see https://github.com/Mrs4s/go-cqhttp/blob/master/docs/guild.md#%E8%8E%B7%E5%8F%96%E9%A2%91%E9%81%93%E6%88%90%E5%91%98%E5%88%97%E8%A1%A8
     * @param  int|string $guild_id 频道ID
     * @return array|bool 返回API调用结果（数组）或异步API调用状态（bool）
     */
    public function getGuildMembers($guild_id)
    {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName($this->prefix, __FUNCTION__),
            'params' => [
                'guild_id' => $guild_id,
            ],
        ], $this->callback);
    }

    /**
     * 发送信息到子频道
     * @see https://github.com/Mrs4s/go-cqhttp/blob/master/docs/guild.md#%E5%8F%91%E9%80%81%E4%BF%A1%E6%81%AF%E5%88%B0%E5%AD%90%E9%A2%91%E9%81%93
     * @param  int|string $guild_id   频道ID
     * @param  int|string $channel_id 子频道ID
     * @param  string     $message    信息内容
     * @return array|bool 返回API调用结果（数组）或异步API调用状态（bool）
     */
    public function sendGuildChannelMsg($guild_id, $channel_id, string $message)
    {
        return $this->processAPI($this->connection, [
            'action' => $this->getActionName($this->prefix, __FUNCTION__),
            'params' => [
                'guild_id' => $guild_id,
                'channel_id' => $channel_id,
                'message' => $message,
            ],
        ], $this->callback);
    }
}
