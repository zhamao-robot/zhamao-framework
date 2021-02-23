<?php


namespace ZM\Annotation\Swoole;


use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 * Class OnMessageEvent
 * @package ZM\Annotation\Swoole
 */
class OnMessageEvent extends OnSwooleEventBase
{
    /**
     * @var string
     */
    public $connect_type = "default";
}