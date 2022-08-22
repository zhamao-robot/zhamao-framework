<?php

declare(strict_types=1);

namespace ZM\Process;

use ZM\Exception\ZMKnownException;
use ZM\Store\FileSystem;

class ProcessStateManager
{
    public static array $process_mode = [];

    /**
     * @param  null|int|string  $id_or_name
     * @throws ZMKnownException
     * @internal
     */
    public static function removeProcessState(int $type, $id_or_name = null)
    {
        switch ($type) {
            case ZM_PROCESS_MASTER:
                $file = ZM_PID_DIR . '/master.json';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_MANAGER:
                $file = ZM_PID_DIR . '/manager.pid';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_WORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                $file = ZM_PID_DIR . '/worker.' . $id_or_name . '.pid';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_USER:
                if (!is_string($id_or_name)) {
                    throw new ZMKnownException('E99999', 'process_name必须为字符串');
                }
                $file = ZM_PID_DIR . '/user.' . $id_or_name . '.pid';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
            case ZM_PROCESS_TASKWORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                $file = ZM_PID_DIR . '/taskworker.' . $id_or_name . '.pid';
                if (file_exists($file)) {
                    unlink($file);
                }
                return;
        }
    }

    /**
     * 用于框架内部获取多进程运行状态的函数
     *
     * @param  mixed            $id_or_name
     * @throws ZMKnownException
     * @return false|int|mixed
     * @internal
     */
    public static function getProcessState(int $type, $id_or_name = null)
    {
        $file = ZM_PID_DIR;
        switch ($type) {
            case ZM_PROCESS_MASTER:
                if (!file_exists($file . '/master.json')) {
                    return false;
                }
                $json = json_decode(file_get_contents($file . '/master.json'), true);
                if ($json !== null) {
                    return $json;
                }
                return false;
            case ZM_PROCESS_MANAGER:
                if (!file_exists($file . '/manager.pid')) {
                    return false;
                }
                return intval(file_get_contents($file . '/manager.pid'));
            case ZM_PROCESS_WORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                if (!file_exists($file . '/worker.' . $id_or_name . '.pid')) {
                    return false;
                }
                return intval(file_get_contents($file . '/worker.' . $id_or_name . '.pid'));
            case ZM_PROCESS_USER:
                if (!is_string($id_or_name)) {
                    throw new ZMKnownException('E99999', 'process_name必须为字符串');
                }
                if (!file_exists($file . '/user.' . $id_or_name . '.pid')) {
                    return false;
                }
                return intval(file_get_contents($file . '/user.' . $id_or_name . '.pid'));
            case ZM_PROCESS_TASKWORKER:
                if (!is_int($id_or_name)) {
                    throw new ZMKnownException('E99999', 'worker_id必须为整数');
                }
                if (!file_exists($file . '/taskworker.' . $id_or_name . '.pid')) {
                    return false;
                }
                return intval(file_get_contents($file . '/taskworker.' . $id_or_name . '.pid'));
            default:
                return false;
        }
    }

    /**
     * 将各进程的pid写入文件，以备后续崩溃及僵尸进程处理使用
     *
     * @param int|string $pid
     * @internal
     */
    public static function saveProcessState(int $type, $pid, array $data = [])
    {
        switch ($type) {
            case ZM_PROCESS_MASTER:
                $file = ZM_PID_DIR . '/master.json';
                $json = [
                    'pid' => intval($pid),
                    'stdout' => $data['stdout'],
                    'daemon' => $data['daemon'],
                ];
                file_put_contents($file, json_encode($json, JSON_UNESCAPED_UNICODE));
                return;
            case ZM_PROCESS_MANAGER:
                $file = ZM_PID_DIR . '/manager.pid';
                file_put_contents($file, strval($pid));
                return;
            case ZM_PROCESS_WORKER:
                $file = ZM_PID_DIR . '/worker.' . $data['worker_id'] . '.pid';
                file_put_contents($file, strval($pid));
                return;
            case ZM_PROCESS_USER:
                $file = ZM_PID_DIR . '/user.' . $data['process_name'] . '.pid';
                file_put_contents($file, strval($pid));
                return;
            case ZM_PROCESS_TASKWORKER:
                $file = ZM_PID_DIR . '/taskworker.' . $data['worker_id'] . '.pid';
                file_put_contents($file, strval($pid));
                return;
        }
    }

    public static function isStateEmpty(): bool
    {
        $ls = FileSystem::scanDirFiles(ZM_PID_DIR, false, true);
        return empty($ls);
    }
}
