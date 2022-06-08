<?php

declare(strict_types=1);

namespace ZM\API\Proxies\Bot;

use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ZM\API\ZMRobot;

class AllBotsProxy extends AbstractBotProxy
{
    /**
     * 在所有机器人实例上调用方法
     *
     * @param  string              $name      方法名
     * @param  array               $arguments 参数
     * @throws ReflectionException
     * @return array<mixed>        返回一个包含所有执行结果的数组，键名为机器人ID
     */
    public function __call(string $name, array $arguments)
    {
        // 如果调用的方法为代理方法，则传入当前代理作为参数
        // 一般此情况代表用户进行嵌套代理，即 `->all()->allGroups()` 等情况
        $reflection = new ReflectionMethod(ZMRobot::class, $name);
        if (($return = $reflection->getReturnType()) && $return instanceof ReflectionNamedType && str_contains($return->getName(), 'Proxy')) {
            logger()->debug("Trying to construct proxy {$name} inside proxy, returning nested proxy.");
            // 插入当前代理作为第一个参数
            array_unshift($arguments, $this);
            return $this->bot->{$name}(...$arguments);
        }

        $result = [];
        // 遍历所有机器人实例
        foreach ($this->bot::getAllRobot() as $bot) {
            logger()->debug("Calling {$name} on bot {$bot->getSelfId()}.");
            $result[$bot->getSelfId()] = $bot->{$name}(...$arguments);
        }
        return $result;
    }
}
