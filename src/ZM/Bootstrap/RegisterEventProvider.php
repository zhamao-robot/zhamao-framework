<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Config\RuntimePreferences;
use ZM\Event\EventProvider;

class RegisterEventProvider implements Bootstrapper
{
    public function bootstrap(RuntimePreferences $preferences): void
    {
        global $ob_event_provider;
        $ob_event_provider = EventProvider::getInstance();
    }
}
