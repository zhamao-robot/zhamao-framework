<?php

declare(strict_types=1);

namespace ZM\Annotation\Command;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use ZM\Annotation\AnnotationBase;

/**
 * Class TerminalCommand
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
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

    public function __construct($command, $alias = '', $description = '')
    {
        $this->command = $command;
        $this->alias = $alias;
        $this->description = $description;
    }
}
