<?php


namespace Module\Example;

use Co;
use Framework\Console;
use Framework\ZMBuf;
use ZM\Annotation\Http\RequestMapping;
use ZM\Annotation\Swoole\SwooleEventAfter;
use ZM\ModBase;
use ZM\Utils\DataProvider;

class HttpImageManager extends ModBase
{
    /**
     * @param $param
     * @RequestMapping("/images/{file_name}")
     */
    public function innerImage($param) {
        $filename = DataProvider::getResourceFolder() . "images/";
        $param_name = $param["file_name"];
        $param_name = strtolower($param_name);
        if (mb_strpos($param_name, "..") !== false) {
            $this->response->status(404);
            $this->response->end();
            return;
        }
        $exp = explode(".", $param_name);
        if (($type = array_pop($exp))) {
            $this->response->header("Content-Type", ZMBuf::config("file_header")[$type] ?? "text/html");
        }
        $this->response->end(Co::readFile($filename . $param_name));
    }
}
