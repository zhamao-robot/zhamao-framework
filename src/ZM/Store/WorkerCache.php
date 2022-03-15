<?php

/** @noinspection PhpMissingReturnTypeInspection */

declare(strict_types=1);

namespace ZM\Store;

use ZM\Config\ZMConfig;

class WorkerCache
{
    public static $config;

    public static $store = [];

    public static $transfer = [];

    public static function get($key)
    {
        $config = self::$config ?? ZMConfig::get('global', 'worker_cache') ?? ['worker' => 0];
        if ($config['worker'] === server()->worker_id) {
            return self::$store[$key] ?? null;
        }
        $action = ['action' => 'getWorkerCache', 'key' => $key, 'cid' => zm_cid()];
        server()->sendMessage(json_encode($action, JSON_UNESCAPED_UNICODE), $config['worker']);
        zm_yield();
        $p = self::$transfer[zm_cid()] ?? null;
        unset(self::$transfer[zm_cid()]);
        return $p;
    }

    public static function set($key, $value, $async = false)
    {
        $config = self::$config ?? ZMConfig::get('global', 'worker_cache') ?? ['worker' => 0];
        if ($config['worker'] === server()->worker_id) {
            self::$store[$key] = $value;
            return true;
        }
        $action = ['action' => $async ? 'asyncSetWorkerCache' : 'setWorkerCache', 'key' => $key, 'value' => $value, 'cid' => zm_cid()];
        return self::processRemote($action, $async, $config);
    }

    public static function hasKey($key, $subkey)
    {
        $config = self::$config ?? ZMConfig::get('global', 'worker_cache') ?? ['worker' => 0];
        if ($config['worker'] === server()->worker_id) {
            return isset(self::$store[$key][$subkey]);
        }
        $action = ['hasKeyWorkerCache', 'key' => $key, 'subkey' => $subkey, 'cid' => zm_cid()];
        return self::processRemote($action, false, $config);
    }

    public static function unset($key, $async = false)
    {
        $config = self::$config ?? ZMConfig::get('global', 'worker_cache') ?? ['worker' => 0];
        if ($config['worker'] === server()->worker_id) {
            unset(self::$store[$key]);
            return true;
        }
        $action = ['action' => $async ? 'asyncUnsetWorkerCache' : 'unsetWorkerCache', 'key' => $key, 'cid' => zm_cid()];
        return self::processRemote($action, $async, $config);
    }

    public static function add($key, int $value, $async = false)
    {
        $config = self::$config ?? ZMConfig::get('global', 'worker_cache') ?? ['worker' => 0];
        if ($config['worker'] === server()->worker_id) {
            if (!isset(self::$store[$key])) {
                self::$store[$key] = 0;
            }
            self::$store[$key] += $value;
            return true;
        }
        $action = ['action' => $async ? 'asyncAddWorkerCache' : 'addWorkerCache', 'key' => $key, 'value' => $value, 'cid' => zm_cid()];
        return self::processRemote($action, $async, $config);
    }

    public static function sub($key, int $value, $async = false)
    {
        $config = self::$config ?? ZMConfig::get('global', 'worker_cache') ?? ['worker' => 0];
        if ($config['worker'] === server()->worker_id) {
            if (!isset(self::$store[$key])) {
                self::$store[$key] = 0;
            }
            self::$store[$key] -= $value;
            return true;
        }
        $action = ['action' => $async ? 'asyncSubWorkerCache' : 'subWorkerCache', 'key' => $key, 'value' => $value, 'cid' => zm_cid()];
        return self::processRemote($action, $async, $config);
    }

    private static function processRemote($action, $async, $config)
    {
        $ss = server()->sendMessage(json_encode($action, JSON_UNESCAPED_UNICODE), $config['worker']);
        if (!$ss) {
            return false;
        }
        if ($async) {
            return true;
        }
        zm_yield();
        $p = self::$transfer[zm_cid()] ?? null;
        unset(self::$transfer[zm_cid()]);
        return $p;
    }
}
