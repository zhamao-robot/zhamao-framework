<?php

declare(strict_types=1);

namespace ZM\Exception;

class SingletonViolationException extends ZMException
{
    public function __construct(string $singleton_class_name)
    {
        parent::__construct(
            "类 {$singleton_class_name} 是单例模式，不允许初始化多个实例。",
            69
        );
    }
}
