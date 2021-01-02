<?php


namespace ZM\Annotation\Swoole;


use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 * Class OnOpenEvent
 * @package ZM\Annotation\Swoole
 */
class OnOpenEvent extends OnSwooleEventBase
{
    /**
     * @var string
     */
    public $connect_type = "default";
}