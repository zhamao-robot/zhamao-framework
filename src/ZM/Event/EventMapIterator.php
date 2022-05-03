<?php

declare(strict_types=1);

namespace ZM\Event;

use Iterator;
use ReturnTypeWillChange;

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
        $this->nextToValid();
    }

    public function current()
    {
        return EventManager::$event_map[$this->class][$this->method][$this->offset];
    }

    public function next(): void
    {
        ++$this->offset;
        $this->nextToValid();
    }

    #[ReturnTypeWillChange]
    public function key()
    {
        return $this->offset;
    }

    public function valid(): bool
    {
        return isset(EventManager::$event_map[$this->class][$this->method][$this->offset]);
    }

    public function rewind(): void
    {
        $this->offset = 0;
    }

    private function nextToValid()
    {
        while ($this->valid() && !is_a($this->current(), $this->event_name, true)) {
            ++$this->offset;
        }
    }
}
