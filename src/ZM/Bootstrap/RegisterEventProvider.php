<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Event\EventProvider;
use ZM\HasRuntimeInfo;

class RegisterEventProvider implements Bootstrapper
{
    public function bootstrap(HasRuntimeInfo $runtime_info): void
    {
        global $ob_event_provider;
        $ob_event_provider = EventProvider::getInstance();
    }
}
