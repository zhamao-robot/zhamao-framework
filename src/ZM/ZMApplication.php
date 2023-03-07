<?php

declare(strict_types=1);

namespace ZM;

use ZM\Command\Server\ServerStartCommand;
use ZM\Exception\SingletonViolationException;
use ZM\Plugin\PluginManager;
use ZM\Plugin\PluginMeta;
use ZM\Plugin\ZMPlugin;

/**
 * 这是一个可以将框架以代码形式启动的一个类，且继承于插件，可以以插件的方式绑定事件回调等
 */
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
        $meta = new PluginMeta(name: 'native', plugin_type: ZM_PLUGIN_TYPE_NATIVE);
        $meta->bindEntity($this);
        PluginManager::addPlugin($meta);
        Framework::getInstance()->init($this->args)->start();
    }
}
