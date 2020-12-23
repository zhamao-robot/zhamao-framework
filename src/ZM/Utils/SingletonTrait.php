<?php


namespace ZM\Utils;


trait SingletonTrait
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @return self
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
