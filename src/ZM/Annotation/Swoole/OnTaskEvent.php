<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class OnTaskEvent
 * @Annotation
 * @Target("METHOD")
 */
class OnTaskEvent extends OnSwooleEventBase
{
}
