<?php


namespace ZM\Utils;


use ZM\API\CQ;
use ZM\Console\Console;
use ZM\Requests\ZMRequest;

class MessageUtil
{
    public static function downloadCQImage($msg, $path = null) {
        $path = $path ?? DataProvider::getDataFolder() . "images/";
        if (!is_dir($path)) mkdir($path);
        $path = realpath($path);
        if ($path === false) {
            Console::warning("指定的路径错误不存在！");
            return false;
        }
        $files = [];
        $cq = CQ::getAllCQ($msg, true);
        foreach ($cq as $v) {
            if ($v->type == "image") {
                $result = ZMRequest::downloadFile($v->params["url"], $path . "/" . $v->params["file"]);
                if ($result === false) {
                    Console::warning("图片 " . $v->params["url"] . " 下载失败！");
                    return false;
                }
                $files[] = $path . "/" . $v->params["file"];
            }
        }
        return $files;
    }
}