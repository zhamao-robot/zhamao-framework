<?php

declare(strict_types=1);

namespace ZM\Process;

use OneBot\Driver\Process\ProcessManager;
use ZM\Exception\ZMKnownException;
use ZM\Store\FileSystem;

class ProcessStateManager
{
    public static array $process_mode = [];

    /**
     * 查看是否为多 Worker 模式，插件可能用得到
     */
    public static function isMultiWorkers(): bool
    {
        return (self::$process_mode['worker'] ?? 1) > 1;
    }

    public static function isTaskWorker(): bool
    {
        return (ProcessManager::getProcessType() & ONEBOT_PROCESS_TASKWORKER) !== 0;
    }

    /**
     * 删除进程运行状态
     *
     * @throws ZMKnownException
     * @internal
     */
    public static function removeProcessState(int $type, null|int|string $id_or_name = null): void
    {
        switch ($type) {
            case ZM_PROCESS_MASTER:
                $file = zm_dir(ZM_STATE_DIR . '/master.json');
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_MANAGER:
                $file = zm_dir(ZM_STATE_DIR . '/manager.pid');
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_WORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                $file = zm_dir(ZM_STATE_DIR . '/worker.' . $id_or_name . '.pid');
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_USER:
                if (!is_string($id_or_name)) {
                    throw new ZMKnownException('E99999', 'process_name必须为字符串');
                }
                $file = zm_dir(ZM_STATE_DIR . '/user.' . $id_or_name . '.pid');
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_TASKWORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                $file = zm_dir(ZM_STATE_DIR . '/taskworker.' . $id_or_name . '.pid');
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
        }
    }

    /**
     * 用于框架内部获取多进程运行状态的函数
     *
     * @return false|int|mixed
     * @throws ZMKnownException
     * @internal
     */
    public static function getProcessState(int $type, mixed $id_or_name = null): mixed
    {
        $file = ZM_STATE_DIR;
        switch ($type) {
            case ZM_PROCESS_MASTER:
                if (!file_exists(zm_dir($file . '/master.json'))) {
                    return false;
                }
                $json = json_decode(file_get_contents(zm_dir($file . '/master.json')), true);
                return $json ?? false;
            case ZM_PROCESS_MANAGER:
                if (!file_exists(zm_dir($file . '/manager.pid'))) {
                    return false;
                }
                return intval(file_get_contents(zm_dir($file . '/manager.pid')));
            case ZM_PROCESS_WORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                if (!file_exists(zm_dir($file . '/worker.' . $id_or_name . '.pid'))) {
                    return false;
                }
                return intval(file_get_contents(zm_dir($file . '/worker.' . $id_or_name . '.pid')));
            case ZM_PROCESS_USER:
                if (!is_string($id_or_name)) {
                    throw new ZMKnownException('E99999', 'process_name必须为字符串');
                }
                if (!file_exists(zm_dir($file . '/user.' . $id_or_name . '.pid'))) {
                    return false;
                }
                return intval(file_get_contents(zm_dir($file . '/user.' . $id_or_name . '.pid')));
            case ZM_PROCESS_TASKWORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                if (!file_exists(zm_dir($file . '/taskworker.' . $id_or_name . '.pid'))) {
                    return false;
                }
                return intval(file_get_contents(zm_dir($file . '/taskworker.' . $id_or_name . '.pid')));
            default:
                return false;
        }
    }

    /**
     * 将各进程的pid写入文件，以备后续崩溃及僵尸进程处理使用
     *
     * @internal
     */
    public static function saveProcessState(int $type, int|string $pid, array $data = []): void
    {
        switch ($type) {
            case ZM_PROCESS_MASTER:
                $file = zm_dir(ZM_STATE_DIR . '/master.json');
                $json = [
                    'pid' => intval($pid),
                    'stdout' => $data['stdout'],
                    'daemon' => $data['daemon'],
                ];
                file_put_contents($file, json_encode($json, JSON_UNESCAPED_UNICODE));
                return;
            case ZM_PROCESS_MANAGER:
                $file = zm_dir(ZM_STATE_DIR . '/manager.pid');
                file_put_contents($file, strval($pid));
                return;
            case ZM_PROCESS_WORKER:
                $file = zm_dir(ZM_STATE_DIR . '/worker.' . $data['worker_id'] . '.pid');
                file_put_contents($file, strval($pid));
                return;
            case ZM_PROCESS_USER:
                $file = zm_dir(ZM_STATE_DIR . '/user.' . $data['process_name'] . '.pid');
                file_put_contents($file, strval($pid));
                return;
            case ZM_PROCESS_TASKWORKER:
                $file = zm_dir(ZM_STATE_DIR . '/taskworker.' . $data['worker_id'] . '.pid');
                file_put_contents($file, strval($pid));
                return;
        }
    }

    public static function isStateEmpty(): bool
    {
        $ls = FileSystem::scanDirFiles(ZM_STATE_DIR, false, true);
        return empty($ls);
    }
}
