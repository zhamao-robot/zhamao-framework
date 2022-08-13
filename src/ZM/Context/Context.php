<?php

declare(strict_types=1);

namespace ZM\Context;

use ZM\Context\Trait\HttpTrait;

/**
 * 下面是机器人类的方法
 * @method reply($message)                            快速回复消息
 * @method action(string $action, array $params = []) 执行动作
 * @method getArgument(string $name)                  获取BotCommand的参数
 * @method getRawArguments()                          获取裸的参数
 * @method getBotEvent(bool $array = false)           获取事件原对象
 * @method getBotSelf()                               获取机器人自身的信息
 */
class Context implements ContextInterface
{
    use HttpTrait;

    // TODO：完善上下文的方法们
}
