<?php

declare(strict_types=1);

namespace ZM\Module;

/**
 * @since 2.6
 */
class InstantModule extends ModuleBase
{
    public function onEvent($event_class, $params, callable $callable)
    {
        $class = new $event_class();
        foreach ($params as $k => $v) {
            if (is_string($k)) {
                $class->{$k} = $v;
            }
        }
        $class->method = $callable;
        $this->events[] = $class;
    }
}
