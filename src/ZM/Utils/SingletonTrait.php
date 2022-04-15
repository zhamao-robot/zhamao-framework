<?php

declare(strict_types=1);

namespace ZM\Utils;

trait SingletonTrait
{
    /**
     * @deprecated 将会于未来版本移除
     *
     * @var array
     */
    protected static $cached = [];

    /**
     * @var null|static
     */
    protected static $instance;

    /**
     * 获取类实例
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
