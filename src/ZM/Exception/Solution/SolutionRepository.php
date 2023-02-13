<?php

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types=1);

namespace ZM\Exception\Solution;

use DI\Definition\Exception\InvalidDefinition;
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
            InvalidDefinition::class => [
                new Solution('无法解析依赖注入', '请检查依赖注入的类是否存在，或者定义是否正确。', []),
                new Solution(
                    '依赖注入用例错误',
                    '请检查注入的类是否在对应的事件中可用，详情可以查看文档。',
                    ['https://framework.zhamao.xin/components/container/dependencies.html']
                ),
            ],
        };
    }
}
