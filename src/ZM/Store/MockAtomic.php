<?php

declare(strict_types=1);

namespace ZM\Store;

class MockAtomic
{
    private int $num = 0;

    public function set(int $num)
    {
        $this->num = $num;
    }

    public function get(): int
    {
        return $this->num;
    }
}
