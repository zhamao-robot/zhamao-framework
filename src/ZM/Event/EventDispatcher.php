<?php

declare(strict_types=1);

namespace ZM\Event;

use OneBot\Util\Singleton;

class EventDispatcher extends \OneBot\Driver\Event\EventDispatcher
{
    use Singleton;
}
