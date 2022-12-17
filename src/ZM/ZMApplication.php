<?php

declare(strict_types=1);

namespace ZM;

use ZM\Command\Server\ServerStartCommand;
use ZM\Exception\SingletonViolationException;
use ZM\Plugin\ZMPlugin;

class ZMApplication extends ZMPlugin
{
    /** @var null|ZMApplication 存储单例类的变量 */
    private static ?ZMApplication $obj;

    /** @var array 存储要传入的args */
    private array $args = [];

    public function __construct(mixed $dir = null)
    {
        if (self::$obj !== null) {
            throw new SingletonViolationException(self::class);
        }
        self::$obj = $this; // 用于标记已经初始化完成
        parent::__construct($dir ?? (__DIR__ . '/../..'));
        $this->args = ServerStartCommand::exportOptionArray();
    }

    public function withConfig(array $config): ZMApplication
    {
        // TODO: 完成patch config
        return $this;
    }

    public function withArgs(array $args): ZMApplication
    {
        $this->args = array_replace_recursive($this->args, $args);
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        (new Framework($this->args))->init()->start();
    }
}
