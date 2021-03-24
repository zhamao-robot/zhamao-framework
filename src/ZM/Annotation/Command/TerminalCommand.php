<?php


namespace ZM\Annotation\Command;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class TerminalCommand
 * @package ZM\Annotation\Command
 * @Annotation
 * @Target("METHOD")
 */
class TerminalCommand
{
    /**
     * @var string
     * @Required()
     */
    public $command;

    /**
     * @var string
     */
    public $description = "";
}