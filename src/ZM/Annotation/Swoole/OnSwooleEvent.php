<?php


namespace ZM\Annotation\Swoole;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class OnSwooleEvent
 * @Annotation
 * @Target("METHOD")
 * @package ZM\Annotation\Swoole
 */
class OnSwooleEvent extends OnSwooleEventBase
{
    /**
     * @var string
     * @Required
     */
    public $type;

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type) {
        $this->type = $type;
    }
}
