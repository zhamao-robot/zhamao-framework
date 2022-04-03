<?php

declare(strict_types=1);

namespace ZM\API\Proxies\Bot;

use ZM\API\ZMRobot;

/**
 * @mixin ZMRobot
 */
abstract class AbstractBotProxy
{
    /**
     * 传入的机器人实例
     *
     * @var ZMRobot
     */
    protected $bot;

    /**
     * 构造函数
     *
     * @param AbstractBotProxy|ZMRobot $bot 调用此代理的机器人实例
     */
    public function __construct($bot)
    {
        $this->bot = $bot;
    }

    /**
     * 在传入的机器人实例上调用方法
     *
     * @param  string $name      方法名
     * @param  array  $arguments 参数
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->bot->{$name}(...$arguments);
    }

    /**
     * 获取传入的机器人实例的属性
     *
     * @param  string $name 属性名
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->bot->{$name};
    }

    /**
     * 设置传入的机器人实例的属性
     *
     * @param string $name  属性名
     * @param mixed  $value 属性值
     */
    public function __set(string $name, $value)
    {
        $this->bot->{$name} = $value;
    }

    /**
     * 判断传入的机器人实例的属性是否存在
     *
     * @param string $name 属性名
     */
    public function __isset(string $name): bool
    {
        return isset($this->bot->{$name});
    }
}
