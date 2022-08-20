<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use ZM\Utils\SingletonTrait;

/**
 * @internal
 */
class SingletonTraitTest extends TestCase
{
    public function testGetInstance(): void
    {
        $mock = $this->getObjectForTrait(SingletonTrait::class);
        $this->assertEquals($mock, $mock::getInstance());
    }
}
