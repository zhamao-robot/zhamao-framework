<?php

declare(strict_types=1);

namespace ZM\Entity;

use ZM\Annotation\CQ\CQCommand;

class MatchResult
{
    /** @var bool */
    public $status = false;

    /** @var null|CQCommand */
    public $object;

    /** @var array */
    public $match = [];
}
