<?php

declare(strict_types=1);

namespace ZM\Event;

use Iterator;
use ReturnTypeWillChange;
use ZM\Console\Console;

class EventMapIterator implements Iterator
{
    private $offset = 0;

    private $class;

    private $method;

    private $event_name;

    public function __construct($class, $method, $event_name)
    {
        $this->class = $class;
        $this->method = $method;
        $this->event_name = $event_name;
    }

    #[ReturnTypeWillChange]
    public function current()
    {
        Console::debug('从 [' . $this->offset . '] 开始获取');
        return EventManager::$event_map[$this->class][$this->method][$this->offset];
    }

    public function next(): void
    {
        Console::debug('下一个offset为 [' . ++$this->offset . ']');
        $this->nextToValid();
    }

    #[ReturnTypeWillChange]
    public function key()
    {
        Console::debug('返回key：' . $this->offset);
        return isset(EventManager::$event_map[$this->class][$this->method][$this->offset]) ? $this->offset : null;
    }

    public function valid($s = false): bool
    {
        Console::debug(
            "[{$this->offset}] " .
            ($s ? 'valid' : '') . '存在：' .
            (!isset(EventManager::$event_map[$this->class][$this->method][$this->offset]) ? Console::setColor('false', 'red') : ('true' .
                (is_a(EventManager::$event_map[$this->class][$this->method][$this->offset], $this->event_name, true) ? '，是目标对象' : '，不是目标对象')))
        );
        return
            isset(EventManager::$event_map[$this->class][$this->method][$this->offset])
            && is_a(EventManager::$event_map[$this->class][$this->method][$this->offset], $this->event_name, true);
    }

    public function rewind(): void
    {
        Console::debug('回到0');
        $this->offset = 0;
        $this->nextToValid();
    }

    private function nextToValid()
    {
        while (
            isset(EventManager::$event_map[$this->class][$this->method][$this->offset])
            && !is_a(EventManager::$event_map[$this->class][$this->method][$this->offset], $this->event_name, true)
        ) {
            ++$this->offset;
        }
        Console::debug('内部偏移offset为 [' . $this->offset . ']');
    }
}
