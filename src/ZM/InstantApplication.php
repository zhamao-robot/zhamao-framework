<?php

declare(strict_types=1);

namespace ZM;

use Exception;
use ZM\Command\Server\ServerStartCommand;
use ZM\Exception\InitException;
use ZM\Plugin\InstantPlugin;

class InstantApplication extends InstantPlugin
{
    /** @var null|InstantApplication 存储单例类的变量 */
    private static ?InstantApplication $obj;

    /** @var array 存储要传入的args */
    private array $args = [];

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
        parent::__construct($dir ?? (__DIR__ . '/../..'));
        $this->args = ServerStartCommand::exportOptionArray();
    }

    public function withConfig(array $config): InstantApplication
    {
        // TODO: 完成patch config
        return $this;
    }

    public function withArgs(array $args): InstantApplication
    {
        $this->args = array_replace_recursive($this->args, $args);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        (new Framework($this->args))->init()->start();
    }
}
