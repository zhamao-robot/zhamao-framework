<?php

declare(strict_types=1);

namespace ZM;

use Exception;
use ZM\Exception\InitException;
use ZM\Plugin\InstantPlugin;

class InstantApplication extends InstantPlugin
{
    private static $obj;

    /**
     * @param  null|mixed    $dir
     * @throws InitException
     */
    public function __construct($dir = null)
    {
        if (self::$obj !== null) {
            throw new InitException(zm_internal_errcode('E00069') . 'Initializing another Application is not allowed!');
        }
        self::$obj = $this; // 用于标记已经初始化完成
        parent::__construct($dir ?? __DIR__);
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        (new Framework())->init()->start();
    }
}
