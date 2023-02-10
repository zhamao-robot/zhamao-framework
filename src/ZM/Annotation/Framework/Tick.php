<?php

declare(strict_types=1);

namespace ZM\Annotation\Framework;

use Symfony\Contracts\Service\Attribute\Required;
use ZM\Annotation\AnnotationBase;

/**
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("METHOD")
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class Tick extends AnnotationBase
{
    #[Required]
    public int $tick_ms;

    public function __construct(int $tick_ms, public int $worker_id = 0)
    {
        $this->tick_ms = $tick_ms;
    }
}
