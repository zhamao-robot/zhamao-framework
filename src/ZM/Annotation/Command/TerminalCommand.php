<?php

declare(strict_types=1);

namespace ZM\Annotation\Command;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class TerminalCommand
 * @Annotation
 * @Target("METHOD")
 */
class TerminalCommand extends AnnotationBase
{
    /**
     * @var string
     * @Required()
     */
    public $command;

    public $alias = '';

    /**
     * @var string
     */
    public $description = '';
}
