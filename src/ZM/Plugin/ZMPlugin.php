<?php

declare(strict_types=1);

namespace ZM\Plugin;

/**
 * 单文件插件声明类
 */
class ZMPlugin
{
    use Traits\BotActionTrait;
    use Traits\BotCommandTrait;
    use Traits\BotEventTrait;
    use Traits\CronTrait;
    use Traits\EventTrait;
    use Traits\InitTrait;
    use Traits\PluginLoadTrait;
    use Traits\RouteTrait;
    use Traits\PluginPackTrait;
    use Traits\TickTrait;
}
