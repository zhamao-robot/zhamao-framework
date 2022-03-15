<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace ZM\Utils;

trait SingletonTrait
{
    protected static $cached = [];

    /**
     * @var self
     */
    private static $instance;

    /**
     * @return self
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
