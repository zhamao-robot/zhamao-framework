<?php

declare(strict_types=1);

namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class OnSwooleEvent
 * @Annotation
 * @Target("METHOD")
 */
class OnSwooleEvent extends OnSwooleEventBase
{
    /**
     * @var string
     * @Required
     */
    public $type;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }
}
