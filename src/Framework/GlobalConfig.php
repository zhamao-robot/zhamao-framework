<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2019-03-16
 * Time: 13:58
 */

namespace Framework;

/**
 * 请不要diss此class的语法。可能写的很糟糕。
 * Class GlobalConfig
 */
class GlobalConfig
{
    private $config = null;
    public $success = false;

    public function __construct() {
        include_once WORKING_DIR . '/config/global.php';
        global $config;
        $this->success = true;
        $this->config = $config;
    }

    public function get($key) {
        $r = $this->config[$key] ?? null;
        if ($r === null) return null;
        return $r;
    }

    public function getAll() {
        return $this->config;
    }
}
