<?php

declare(strict_types=1);

namespace Tests;

use Prophecy\Prophet;

/**
 * @internal
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    protected Prophet $prophet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prophet = new Prophet();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->prophet->checkPredictions();
    }
}
