<?php

declare(strict_types=1);

namespace ZM\API\Proxies\Bot;

use ReflectionException;
use ZM\API\ZMRobot;

class AllGroupsProxy extends AbstractBotProxy
{
    /**
     * 在传入的机器人实例上调用方法
     *
     * @param  string              $name      方法名
     * @param  array               $arguments 参数
     * @throws ReflectionException
     * @return array<mixed>        返回一个包含所有执行结果的数组，键名为群号
     */
    public function __call(string $name, array $arguments)
    {
        // 如果调用的方法并非群组方法，则直接返回输出
        // 因为目前所有群组方法都是以 `group_id` 作为第一个参数，故以此来判断
        $reflection = new \ReflectionMethod(ZMRobot::class, $name);
        if (!$reflection->getNumberOfParameters() || $reflection->getParameters()[0]->getName() !== 'group_id') {
            logger()->warning("Trying to call non-group method {$name} on AllGroupsProxy, skipped.");
            return $this->bot->{$name}(...$arguments);
        }

        $result = [];
        // 获取并遍历所有群组
        $groups = $this->bot->getGroupList()['data'];
        foreach ($groups as $group) {
            $arguments[0] = $group['group_id'];
            $bot_id = implode_when_necessary($this->bot->getSelfId());
            logger()->debug("Calling {$name} on group {$group['group_id']} on bot {$bot_id}.");
            // 在群组上调用方法
            $result[$group['group_id']] = $this->bot->{$name}(...$arguments);
        }
        return $result;
    }
}
