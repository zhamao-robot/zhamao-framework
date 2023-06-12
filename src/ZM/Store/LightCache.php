<?php

declare(strict_types=1);

namespace ZM\Store;

use Exception;
use Swoole\Table;
use ZM\Annotation\Swoole\OnSave;
use ZM\Console\Console;
use ZM\Event\EventDispatcher;
use ZM\Exception\LightCacheException;
use ZM\Exception\ZMException;
use ZM\Framework;
use ZM\Utils\Manager\WorkerManager;

class LightCache
{
    public static $last_error = '';

    /** @var null|Table */
    private static $kv_table;

    private static $config = [];

    /**
     * @param  array      $config 配置
     * @throws Exception
     * @return bool|mixed 返回失败（false）或创建SwooleTable成功结果
     */
    public static function init(array $config)
    {
        self::$config = $config;
        self::$kv_table = new Table($config['size'], $config['hash_conflict_proportion']);
        self::$kv_table->column('value', Table::TYPE_STRING, $config['max_strlen']);
        self::$kv_table->column('expire', Table::TYPE_INT);
        self::$kv_table->column('data_type', Table::TYPE_STRING, 8);
        $result = self::$kv_table->create();
        // 加载内容
        if ($result === true && isset($config['persistence_path'])) {
            if (file_exists($config['persistence_path'])) {
                $r = json_decode(file_get_contents($config['persistence_path']), true);
                if ($r === null) {
                    $r = [];
                }
                foreach ($r as $k => $v) {
                    $write = self::set($k, $v);
                    Console::verbose('Writing LightCache: ' . $k);
                    if ($write === false) {
                        self::$last_error = zm_internal_errcode('E00051') . '可能是由于 Hash 冲突过多导致动态空间无法分配内存';
                        return false;
                    }
                }
            }
        }
        if ($result === false) {
            self::$last_error = zm_internal_errcode('E00050') . '系统内存不足，申请失败';
        } else {
            $obj = Framework::loadFrameworkState();
            foreach (($obj['expiring_light_cache'] ?? []) as $k => $v) {
                $value = $v['value'];
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    if (strlen($value) >= self::$config['max_strlen']) {
                        return false;
                    }
                    $data_type = 'json';
                } elseif (is_string($value)) {
                    $data_type = '';
                } elseif (is_int($value)) {
                    $data_type = 'int';
                } elseif (is_bool($value)) {
                    $data_type = 'bool';
                    $value = json_encode($value);
                } else {
                    return false;
                }
                $result = self::$kv_table->set((string) $k, [
                    'value' => $value,
                    'expire' => $v['expire'],
                    'data_type' => $data_type,
                ]);
                if ($result === false) {
                    return false;
                }
            }
        }
        return $result;
    }

    /**
     * @throws ZMException
     * @return null|mixed
     */
    public static function get(string $key)
    {
        if (self::$kv_table === null) {
            throw new LightCacheException('E00048', 'not initialized LightCache');
        }
        self::checkExpire($key);
        $r = self::$kv_table->get($key);
        return $r === false ? null : self::parseGet($r);
    }

    /**
     * @throws ZMException
     * @return null|mixed
     */
    public static function getExpire(string $key)
    {
        if (self::$kv_table === null) {
            throw new LightCacheException('E00048', 'not initialized LightCache');
        }
        self::checkExpire($key);
        $r = self::$kv_table->get($key, 'expire');
        return $r === false ? null : $r - time();
    }

    /**
     * @throws ZMException
     * @return null|mixed
     * @since 2.4.3
     */
    public static function getExpireTS(string $key)
    {
        if (self::$kv_table === null) {
            throw new LightCacheException('E00048', 'not initialized LightCache');
        }
        self::checkExpire($key);
        $r = self::$kv_table->get($key, 'expire');
        return $r === false ? null : $r;
    }

    /**
     * @param  array|int|string $value
     * @throws ZMException
     * @return bool
     */
    public static function set(string $key, $value, int $expire = -1)
    {
        if (self::$kv_table === null) {
            throw new LightCacheException('E00048', 'not initialized LightCache');
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            if (strlen($value) >= self::$config['max_strlen']) {
                return false;
            }
            $data_type = 'json';
        } elseif (is_string($value)) {
            $data_type = '';
        } elseif (is_int($value)) {
            $data_type = 'int';
        } elseif (is_bool($value)) {
            $data_type = 'bool';
            $value = json_encode($value);
        } else {
            throw new LightCacheException('E00049', 'Only can set string, array and int');
        }
        try {
            return self::$kv_table->set($key, [
                'value' => $value,
                'expire' => $expire >= 0 ? $expire + time() : $expire,
                'data_type' => $data_type,
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param  mixed       $value
     * @throws ZMException
     * @return bool
     */
    public static function update(string $key, $value)
    {
        if (self::$kv_table === null) {
            throw new LightCacheException('E00048', 'not initialized LightCache.');
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            if (strlen($value) >= self::$config['max_strlen']) {
                return false;
            }
            $data_type = 'json';
        } elseif (is_string($value)) {
            $data_type = '';
        } elseif (is_int($value)) {
            $data_type = 'int';
        } elseif (is_bool($value)) {
            $data_type = 'bool';
            $value = json_encode($value);
        } else {
            throw new LightCacheException('E00048', 'Only can set string, array and int');
        }
        try {
            if (self::$kv_table->get($key) === false) {
                return false;
            }
            return self::$kv_table->set($key, [
                'value' => $value,
                'data_type' => $data_type,
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getMemoryUsage()
    {
        return self::$kv_table->getMemorySize();
    }

    /**
     * @throws Exception
     */
    public static function isset(string $key): bool
    {
        return self::get($key) !== null;
    }

    public static function unset(string $key)
    {
        return self::$kv_table->del($key);
    }

    public static function getAll(): array
    {
        $r = [];
        $del = [];
        foreach (self::$kv_table as $k => $v) {
            if ($v['expire'] <= time() && $v['expire'] >= 0) {
                $del[] = $k;
                continue;
            }
            $r[$k] = self::parseGet($v);
        }
        foreach ($del as $v) {
            self::unset($v);
        }
        return $r;
    }

    public static function addPersistence($key)
    {
        if (file_exists(self::$config['persistence_path'])) {
            $r = json_decode(file_get_contents(self::$config['persistence_path']), true);
            if ($r === null) {
                $r = [];
            }
            if (!isset($r[$key])) {
                $r[$key] = null;
            }
            file_put_contents(self::$config['persistence_path'], json_encode($r, 64 | 128 | 256));
            return true;
        }
        return false;
    }

    public static function removePersistence($key)
    {
        if (file_exists(self::$config['persistence_path'])) {
            $r = json_decode(file_get_contents(self::$config['persistence_path']), true);
            if ($r === null) {
                $r = [];
            }
            if (isset($r[$key])) {
                unset($r[$key]);
            }
            file_put_contents(self::$config['persistence_path'], json_encode($r, 64 | 128 | 256));
            return true;
        }
        return false;
    }

    /**
     * 这个只能在唯一一个工作进程中执行
     * @throws Exception
     */
    public static function savePersistence()
    {
        if (server()->worker_id !== MAIN_WORKER) {
            WorkerManager::sendActionToWorker(MAIN_WORKER, 'save_persistence', []);
            return;
        }
        $dispatcher = new EventDispatcher(OnSave::class);
        $dispatcher->dispatchEvents();

        if (self::$kv_table === null) {
            return;
        }

        if (!empty(self::$config['persistence_path'])) {
            if (file_exists(self::$config['persistence_path'])) {
                $r = json_decode(file_get_contents(self::$config['persistence_path']), true);
            } else {
                $r = [];
            }
            if ($r === null) {
                $r = [];
            }
            foreach ($r as $k => $v) {
                Console::verbose('Saving ' . $k);
                $r[$k] = self::get($k);
            }
            file_put_contents(self::$config['persistence_path'], json_encode($r, 64 | 128 | 256));
        }

        $obj = Framework::loadFrameworkState();
        $obj['expiring_light_cache'] = [];
        $del = [];
        foreach (self::$kv_table as $k => $v) {
            if ($v['expire'] <= time() && $v['expire'] >= 0) {
                $del[] = $k;
            } elseif ($v['expire'] > time()) {
                $obj['expiring_light_cache'][$k] = [
                    'expire' => $v['expire'],
                    'value' => self::parseGet($v),
                ];
            }
        }
        foreach ($del as $v) {
            self::unset($v);
        }
        Framework::saveFrameworkState($obj);
        Console::verbose('Saved.');
    }

    private static function checkExpire($key)
    {
        if (($expire = self::$kv_table->get($key, 'expire')) >= 0) {
            if ($expire <= time()) {
                self::$kv_table->del($key);
            }
        }
    }

    private static function parseGet($r)
    {
        switch ($r['data_type']) {
            case 'json':
            case 'int':
            case 'bool':
                return json_decode($r['value'], true);
            case '':
            default:
                return $r['value'];
        }
    }
}
