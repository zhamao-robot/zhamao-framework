<?php

declare(strict_types=1);

namespace ZM\Event;

use ZM\Annotation\AnnotationBase;

class EventTracer
{
    /**
     * 获取当前注解事件的注解类，如CQCommand对象
     * @return null|AnnotationBase
     */
    public static function getCurrentEvent()
    {
        $list = debug_backtrace();
        foreach ($list as $v) {
            if ((($v['object'] ?? null) instanceof EventDispatcher) && $v['function'] == 'dispatchEvent') {
                return $v['args'][0];
            }
        }
        return null;
    }

    /**
     * 获取当前注解事件的中间件列表
     * @return null|array|mixed
     */
    public static function getCurrentEventMiddlewares()
    {
        $current_event = self::getCurrentEvent();
        if (!isset($current_event->class, $current_event->method)) {
            return null;
        }
        return EventManager::$middleware_map[$current_event->class][$current_event->method] ?? [];
    }

    public static function getEventTraceList()
    {
        $result = [];
        $list = debug_backtrace();
        foreach ($list as $v) {
            if ((($v['object'] ?? null) instanceof EventDispatcher) && $v['function'] == 'dispatchEvent') {
                $result[] = $v['args'][0];
            }
        }
        return $result;
    }
}
