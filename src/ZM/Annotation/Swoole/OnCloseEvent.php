<?php


namespace ZM\Annotation\Swoole;


use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 * Class OnCloseEvent
 * @package ZM\Annotation\Swoole
 */
class OnCloseEvent extends OnSwooleEventBase
{
    /**
     * @var string
     */
    public $connect_type = "default";
}