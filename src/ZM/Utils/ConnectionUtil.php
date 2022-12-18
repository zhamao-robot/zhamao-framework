<?php

declare(strict_types=1);

namespace ZM\Utils;

use OneBot\Driver\Process\ProcessManager;
use ZM\Process\ProcessStateManager;

class ConnectionUtil
{
    /**
     * @internal
     * @var int WebSocket 连接统计
     */
    public static int $connection_count = 0;

    /**
     * @var array WebSocket 连接元信息
     */
    private static array $connection_handles = [];

    /**
     * 添加连接元信息
     *
     * @param int   $fd     WS 连接 ID
     * @param array $handle WS 连接元信息
     */
    public static function addConnection(int $fd, array $handle = []): bool
    {
        ++self::$connection_count;
        // 超过1024不行
        if (self::$connection_count >= 1024) {
            return false;
        }
        self::$connection_handles[$fd] = $handle;
        // 这里下面为连接准入，允许接入反向 WS
        if (ProcessStateManager::$process_mode['worker'] > 1) {
            // 文件名格式为 .WS{fd}.{pid}，文件内容是 impl 名称的 JSON 格式
            file_put_contents(zm_dir(ZM_STATE_DIR . '/.WS' . $fd . '.' . ProcessManager::getProcessId()), json_encode($handle));
        }
        return true;
    }

    /**
     * 更改、覆盖或合并连接元信息
     * @param int   $fd     WS 连接 ID
     * @param array $handle WS 连接元信息
     */
    public static function setConnection(int $fd, array $handle): void
    {
        self::$connection_handles[$fd] = array_merge(self::$connection_handles[$fd] ?? [], $handle);
        // 这里下面为连接准入，允许接入反向 WS
        if (ProcessStateManager::$process_mode['worker'] > 1) {
            // 文件名格式为 .WS{fd}.{pid}，文件内容是 impl 名称的 JSON 格式
            file_put_contents(zm_dir(ZM_STATE_DIR . '/.WS' . $fd . '.' . ProcessManager::getProcessId()), json_encode(self::$connection_handles[$fd]));
        }
    }

    /**
     * 删除连接元信息
     *
     * @param int $fd WS 连接 ID
     */
    public static function removeConnection(int $fd): void
    {
        --self::$connection_count;
        unset(self::$connection_handles[$fd]);
        // 这里下面为连接准入，允许接入反向 WS
        if (ProcessStateManager::$process_mode['worker'] > 1) {
            // 文件名格式为 .WS{fd}.{pid}，文件内容是 impl 名称的 JSON 格式
            @unlink(zm_dir(ZM_STATE_DIR . '/.WS' . $fd . '.' . ProcessManager::getProcessId()));
        }
    }
}
