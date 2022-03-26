<?php

declare(strict_types=1);

namespace ZM\DB;

use Doctrine\ORM\Decorator\EntityManagerDecorator;

class SwooleEntityManager extends EntityManagerDecorator
{
    /**
     * SwooleEntityManager constructor.
     */
    public function __construct()
    {
        // 此处使用了封装后的 EntityManager
        $this->wrapped = new SwooleEntityManagerWrapper();
    }
}
