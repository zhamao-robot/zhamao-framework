<?php


namespace ZM\Annotation\Module;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class LoadBuffer
 * @package ZM\Annotation\Module
 * @Annotation
 * @Target("CLASS")
 */
class LoadBuffer
{
    /**
     * @var string
     * @Required()
     */
    public $buf_name;

    /** @var string $sub_folder */
    public $sub_folder = null;
}
