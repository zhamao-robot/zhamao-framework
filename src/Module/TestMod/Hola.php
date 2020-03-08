<?php


namespace Module\TestMod;

use Framework\Console;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Swoole\SwooleEventAt;
use ZM\Connection\WSConnection;
use ZM\ModBase;

/**
 * Class Hola
 * @package Module\TestMod
 * @noinspection PhpUnused
 */
class Hola extends ModBase
{

    /**
     * @SwooleEventAt(type="open",rule="connectType:unknown")
     * @param WSConnection $conn
     */
    public function onUnknownConnect() {
        Console::warning("Unknown websocket has been shutdown.");
        Console::stackTrace();
        $this->connection->close();
    }

    /** @RequestMapping("/ping") */
    public function ping(){
        return "OKK";
    }
}