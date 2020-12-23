<?php


namespace ZM\Http;


use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Utils\HttpUtil;

class StaticFileHandler
{
    public function __construct($filename, $path) {
        $full_path = realpath($path . "/" . $filename);
        $response = ctx()->getResponse();
        Console::debug("Full path: ".$full_path);
        if ($full_path !== false) {
            if (strpos($full_path, $path) !== 0) {
                $response->status(403);
                $response->end("403 Forbidden");
                return true;
            } else {
                if(is_file($full_path)) {
                    $exp = strtolower(pathinfo($full_path)['extension'] ?? "unknown");
                    $response->setHeader("Content-Type", ZMConfig::get("file_header")[$exp] ?? "application/octet-stream");
                    $response->end(file_get_contents($full_path));
                    return true;
                }
            }
        }
        $response->status(404);
        $response->end(HttpUtil::getHttpCodePage(404));
        return true;
    }
}
