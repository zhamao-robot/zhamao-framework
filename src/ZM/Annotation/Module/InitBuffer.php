<?php


namespace ZM\Annotation\Module;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class InitBuffer
 * @Annotation
 * @Target("CLASS")
 * @package ZM\Annotation\Module
 */
class InitBuffer
{
    /** @var string @Required() */
    public $buf_name;
}