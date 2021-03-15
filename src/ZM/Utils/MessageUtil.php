<?php


namespace ZM\Utils;


use ZM\API\CQ;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Framework;
use ZM\Requests\ZMRequest;

class MessageUtil
{
    /**
     * 下载消息中 CQ 码的所有图片，通过 url
     * @param $msg
     * @param null $path
     * @return array|false
     */
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

    /**
     * 检查消息中是否含有图片 CQ 码
     * @param $msg
     * @return bool
     */
    public static function containsImage($msg) {
        $cq = CQ::getAllCQ($msg, true);
        foreach ($cq as $v) {
            if ($v->type == "image") {
                return true;
            }
        }
        return false;
    }

    /**
     * 通过本地地址返回图片的 CQ 码
     * type == 0 : 返回图片的 base64 CQ 码
     * type == 1 : 返回图片的 file://路径 CQ 码（路径必须为绝对路径）
     * type == 2 : 返回图片的 http://xxx CQ 码（默认为 /images/ 路径就是文件对应所在的目录）
     * @param $file
     * @param int $type
     * @return string
     */
    public static function getImageCQFromLocal($file, $type = 0) {
        switch ($type) {
            case 0:
                return CQ::image("base64://" . base64_encode(file_get_contents($file)));
            case 1:
                return CQ::image("file://" . $file);
            case 2:
                $info = pathinfo($file);
                return CQ::image(ZMConfig::get("global", "http_reverse_link") . "/images/" . $info["basename"]);
        }
        return "";
    }
}