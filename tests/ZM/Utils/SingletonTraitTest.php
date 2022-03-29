<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use ZM\Utils\SingletonTrait;
use PHPUnit\Framework\TestCase;

class SingletonTraitTest extends TestCase
{
    public function testGetInstance(): void
    {
        $mock = $this->getObjectForTrait(SingletonTrait::class);
        $this->assertEquals($mock, $mock::getInstance());
    }
}
