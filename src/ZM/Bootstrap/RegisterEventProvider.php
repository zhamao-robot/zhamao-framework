<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Event\EventProvider;

class RegisterEventProvider
{
    public function bootstrap(array $config): void
    {
        global $ob_event_provider;
        $ob_event_provider = EventProvider::getInstance();
    }
}
