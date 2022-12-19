<?php

declare(strict_types=1);

namespace ZM\Plugin;

use Choir\Http\HttpFactory;
use OneBot\Driver\Event\StopException;
use OneBot\Driver\Event\WebSocket\WebSocketOpenEvent;
use ZM\Annotation\AnnotationParser;
use ZM\Annotation\OneBot\BotCommand;
use ZM\Annotation\OneBot\CommandArgument;
use ZM\Utils\ConnectionUtil;

class OneBot12Adapter extends ZMPlugin
{
    public function __construct(string $submodule = '', ?AnnotationParser $parser = null)
    {
        parent::__construct(__DIR__);
        switch ($submodule) {
            case '':
            case 'onebot12':
                // 处理所有 OneBot 12 的反向 WS 握手事件
                $this->addEvent(WebSocketOpenEvent::class, [$this, 'handleWSReverseInput']);
                // 处理和声明所有 BotCommand 下的 CommandArgument
                $parser->addSpecialParser(BotCommand::class, [$this, 'parseBotCommand']);
                // 不需要给列表写入 CommandArgument
                $parser->addSpecialParser(CommandArgument::class, [$this, 'parseCommandArgument']);
                break;
            case 'onebot12-ban-other-ws':
                // 禁止其他类型的 WebSocket 客户端接入
                $this->addEvent(WebSocketOpenEvent::class, [$this, 'handleUnknownWSReverseInput'], 1);
                break;
        }
    }

    /**
     * 将 BotCommand 假设含有 CommandArgument 的话，就注册到参数列表中
     *
     * @param BotCommand $command                 命令对象
     * @param null|array $same_method_annotations 同一个方法的所有注解
     */
    public function parseBotCommand(BotCommand $command, ?array $same_method_annotations = null): ?bool
    {
        if ($same_method_annotations === null) {
            return null;
        }
        foreach ($same_method_annotations as $v) {
            if ($v instanceof CommandArgument) {
                $command->withArgumentObject($v);
            }
        }
        return null;
    }

    /**
     * 忽略解析记录 CommandArgument 注解
     */
    public function parseCommandArgument(): ?bool
    {
        return true;
    }

    /**
     * @throws StopException
     */
    public function handleUnknownWSReverseInput(WebSocketOpenEvent $event)
    {
        // 判断是不是 OneBot 12 反向 WS 连进来的，通过 Sec-WebSocket-Protocol 头
        $line = explode('.', $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol'), 2);
        if ($line[0] !== '12') {
            logger()->warning('不允许接入除 OneBot 12 以外的 WebSocket Client');
            $event->withResponse(HttpFactory::createResponse(403, 'Forbidden'));
            $event->stopPropagation();
        }
    }

    /**
     * 接入和认证反向 WS 的连接
     * @throws StopException
     */
    public function handleWSReverseInput(WebSocketOpenEvent $event): void
    {
        // 判断是不是 OneBot 12 反向 WS 连进来的，通过 Sec-WebSocket-Protocol 头
        $line = explode('.', $event->getRequest()->getHeaderLine('Sec-WebSocket-Protocol'), 2);
        if ($line[0] === '12') {
            logger()->info('检测到 OneBot 12 反向 WS 连接，正在进行认证...');
            // 是 OneBot 12 标准的，准许接入，进行鉴权
            $request = $event->getRequest();
            if (($stored_token = $event->getSocketConfig()['access_token'] ?? '') !== '') {
                // 测试 Header
                $token = $request->getHeaderLine('Authorization');
                if ($token === '') {
                    // 测试 Query
                    $token = $request->getQueryParams()['access_token'] ?? '';
                }
                $token = explode('Bearer ', $token);
                $info = ['impl' => $line[1] ?? 'unknown'];
                if (!isset($token[1]) || $token[1] !== $stored_token) { // 没有 token，鉴权失败
                    logger()->warning('OneBot 12 反向 WS 连接鉴权失败，拒绝接入');
                    $event->withResponse(HttpFactory::createResponse(401, 'Unauthorized'));
                    $event->stopPropagation();
                }
            }
        }
        // 设置 OneBot 相关的东西
        ConnectionUtil::setConnection($event->getFd(), $info ?? []);
    }
}
