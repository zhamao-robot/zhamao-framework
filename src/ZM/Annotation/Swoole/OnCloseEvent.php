<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 * Class OnCloseEvent
 */
class OnCloseEvent extends OnSwooleEventBase
{
    /**
     * @var string
     */
    public $connect_type = 'default';
}
