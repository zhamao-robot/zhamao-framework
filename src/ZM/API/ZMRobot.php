<?php

declare(strict_types=1);

namespace ZM\API;

use ZM\API\Proxies\Bot as Proxies;

/**
 * Class ZMRobot
 * @since 1.2
 * @version V11
 */
class ZMRobot extends OneBotV11
{
    /**
     * 获取一个会在所有机器人实例上执行的代理
     */
    public function all(Proxies\AbstractBotProxy $proxy = null): Proxies\AllBotsProxy
    {
        $bot = $proxy ?: $this;
        $bot_id = implode_when_necessary($bot->getSelfId());
        logger()->debug("Constructing AllBotsProxy for ZMRobot({$bot_id})");
        return new Proxies\AllBotsProxy($bot);
    }

    /**
     * 获取一个会在所有群上执行的代理
     */
    public function allGroups(Proxies\AbstractBotProxy $proxy = null): Proxies\AllGroupsProxy
    {
        $bot = $proxy ?: $this;
        $bot_id = implode_when_necessary($bot->getSelfId());
        logger()->debug("Constructing AllGroupsProxy for ZMRobot({$bot_id})");
        return new Proxies\AllGroupsProxy($bot);
    }
}
