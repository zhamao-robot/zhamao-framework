<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 * @since 2.7.0
 */
class OnManagerStartEvent extends OnSwooleEventBase
{
}
