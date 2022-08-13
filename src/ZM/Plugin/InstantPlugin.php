<?php

declare(strict_types=1);

namespace ZM\Plugin;

use ZM\Annotation\Http\Route;
use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\BotEvent;

class InstantPlugin
{
    /** @var string 插件目录 */
    protected $dir;

    /** @var array 机器人事件列表 */
    protected $bot_events = [];

    /** @var array 机器人指令列表 */
    protected $bot_commands = [];

    /** @var array 全局的事件列表 */
    protected $events = [];

    /** @var array 注册的路由列表 */
    protected $routes = [];

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function addBotEvent(BotEvent $event)
    {
        $this->bot_events[] = $event;
    }

    public function addBotCommand(BotCommand $command)
    {
        $this->bot_commands[] = $command;
    }

    public function registerEvent(string $event_name, callable $callback, int $level = 20)
    {
        $this->events[] = [$event_name, $callback, $level];
    }

    public function addHttpRoute(Route $route)
    {
        $this->routes[] = $route;
    }

    public function getBotEvents(): array
    {
        return $this->bot_events;
    }

    public function getBotCommands(): array
    {
        return $this->bot_commands;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
