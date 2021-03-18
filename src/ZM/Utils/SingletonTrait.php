<?php /** @noinspection PhpUnused */


namespace ZM\Utils;


trait SingletonTrait
{
    /**
     * @var self
     */
    private static $instance;

    protected static $cached = [];

    /**
     * @return self
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
