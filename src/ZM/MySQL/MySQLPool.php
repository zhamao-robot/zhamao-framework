<?php /** @noinspection PhpComposerExtensionStubsInspection */

/** @noinspection PhpReturnDocTypeMismatchInspection */


namespace ZM\MySQL;


use PDO;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOProxy;

class MySQLPool extends PDOPool
{
    private $count = 0;

    public function __construct(PDOConfig $config, int $size = self::DEFAULT_SIZE) {
        parent::__construct($config, $size);
    }

    /**
     * @return PDO|PDOProxy|void
     */
    public function getConnection() {
        $this->count++;
        return parent::get();
    }

    /**
     * @param PDO|PDOProxy $connection
     */
    public function putConnection($connection) {
        $this->count--;
        parent::put($connection);
    }
}