<?php

declare(strict_types=1);

namespace ZM\Exception\Solution;

use NunoMaduro\Collision\Contracts\SolutionsRepository;

class SolutionRepository implements SolutionsRepository
{
    /**
     * @return Solution[]
     */
    public function getFromThrowable(\Throwable $throwable): array
    {
        return match ($throwable::class) {
            default => [],
        };
    }
}
