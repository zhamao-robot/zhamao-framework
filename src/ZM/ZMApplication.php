<?php

declare(strict_types=1);

namespace ZM;

use ZM\Command\Server\ServerStartCommand;
use ZM\Exception\SingletonViolationException;
use ZM\Plugin\PluginManager;
use ZM\Plugin\PluginMeta;
use ZM\Plugin\ZMPlugin;

class ZMApplication extends ZMPlugin
{
    /** @var null|ZMApplication 存储单例类的变量 */
    private static ?ZMApplication $obj = null;

    /** @var array 存储要传入的args */
    private array $args = [];

    /**
     * @throws SingletonViolationException
     */
    public function __construct()
    {
        if (self::$obj !== null) {
            throw new SingletonViolationException(self::class);
        }
        self::$obj = $this; // 用于标记已经初始化完成
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
        $meta = new PluginMeta(['name' => 'native'], ZM_PLUGIN_TYPE_NATIVE);
        $meta->bindEntity($this);
        PluginManager::addPlugin($meta);
        (new Framework($this->args))->init()->start();
    }
}
