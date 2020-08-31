<?php

use Swoole\Coroutine\Http\Client;

Co\run(function (){
    hello:
    global $terminal_id, $port;
    $client = new Client("127.0.0.1", $port);
    $client->set(['websocket_mask' => true]);
    $client->setHeaders(["x-terminal-id" => $terminal_id, 'x-pid' => posix_getppid()]);
    $ret = $client->upgrade("/?type=terminal");
    if ($ret) {
        while (true) {
            $line = fgets(STDIN);
            if ($line !== false) {
                $r = $client->push(trim($line));
                if (trim($line) == "reload" || trim($line) == "r" || trim($line) == "stop") {
                    break;
                }
                if($r === false) {
                    echo "Unable to connect framework terminal, connection closed. Trying to reconnect after 5s.\n";
                    sleep(5);
                    goto hello;
                }
            } else {
                break;
            }
        }
    } else {
        echo "Unable to connect framework terminal. port: $port\n";
    }
});

