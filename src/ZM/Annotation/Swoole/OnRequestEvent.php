<?php


namespace ZM\Annotation\Swoole;


use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 * Class OnRequestEvent
 * @package ZM\Annotation\Swoole
 */
class OnRequestEvent extends OnSwooleEventBase
{
}