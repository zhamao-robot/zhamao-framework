<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 * Class OnRequestEvent
 */
class OnRequestEvent extends OnSwooleEventBase
{
}
