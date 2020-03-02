<?php


namespace ZM\Annotation\Module;


use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class SaveBuffer
 * @Annotation
 * @Target("CLASS")
 * @package ZM\Annotation\Module
 */
class SaveBuffer
{
    /**
     * @var string
     *@Required()
     */
    public $buf_name;
    /** @var string|null $sub_folder */
    public $sub_folder = null;
}