<?php


namespace ZM\Utils;


use Co;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Event\EventManager;
use ZM\Http\Response;

class HttpUtil
{
    public static function parseUri($request, $response, $uri, &$node, &$params) {
        $uri = explode("/", $uri);
        $uri = array_diff($uri, ["..", "", "."]);
        $node = EventManager::$req_mapping;
        $params = [];
        while (true) {
            $r = array_shift($uri);
            if ($r === null) break;
            if (($cnt = count($node["son"] ?? [])) == 1) {
                if (isset($node["param_route"])) {
                    foreach ($node["son"] as $k => $v) {
                        if ($v["id"] == $node["param_route"]) {
                            $node = $v;
                            $params[mb_substr($v["name"], 1, -1)] = $r;
                            continue 2;
                        }
                    }
                } elseif ($node["son"][0]["name"] == $r) {
                    $node = $node["son"][0];
                    continue;
                }
            } elseif ($cnt >= 1) {
                if (isset($node["param_route"])) {
                    foreach ($node["son"] as $k => $v) {
                        if ($v["id"] == $node["param_route"]) {
                            $node = $v;
                            $params[mb_substr($v["name"], 1, -1)] = $r;
                            continue 2;
                        }
                    }
                }
                foreach ($node["son"] as $k => $v) {
                    if ($v["name"] == $r) {
                        $node = $v;
                        continue 2;
                    }
                }
            }
            if (ZMConfig::get("global", "static_file_server")["status"]) {
                HttpUtil::handleStaticPage($request->server["request_uri"], $response);
                return null;
            }
        }
        return !isset($node["route"]) ? false : true;
    }

    public static function getHttpCodePage(int $http_code) {
        if (isset(ZMConfig::get("global", "http_default_code_page")[$http_code])) {
            return Co::readFile(DataProvider::getResourceFolder() . "html/" . ZMConfig::get("global", "http_default_code_page")[$http_code]);
        } else return null;
    }

    /**
     * @param $uri
     * @param Response|\Swoole\Http\Response $response
     * @param array $settings
     * @return bool
     */
    public static function handleStaticPage($uri, $response, $settings = []) {
        $base_dir = $settings["document_root"] ?? ZMConfig::get("global", "static_file_server")["document_root"];
        $base_index = $settings["document_index"] ?? ZMConfig::get("global", "static_file_server")["document_index"];
        $path = realpath($base_dir . urldecode($uri));
        if ($path !== false) {
            if (is_dir($path)) $path = $path . '/';
            $work = realpath($base_dir) . '/';
            if (strpos($path, $work) !== 0) {
                Console::info("[403] " . $uri);
                self::responseCodePage($response, 403);
                return true;
            }
            if (is_dir($path)) {
                if(mb_substr($uri, -1, 1) != "/") {
                    Console::info("[302] " . $uri);
                    $response->redirect($uri."/", 302);
                    return true;
                }
                foreach ($base_index as $vp) {
                    if (is_file($path . "/" . $vp)) {
                        Console::info("[200] " . $uri);
                        $exp = strtolower(pathinfo($path . $vp)['extension'] ?? "unknown");
                        $response->setHeader("Content-Type", ZMConfig::get("file_header")[$exp] ?? "application/octet-stream");
                        $response->end(file_get_contents($path . $vp));
                        return true;
                    }
                }
            } elseif (is_file($path)) {
                Console::info("[200] " . $uri);
                $exp = strtolower(pathinfo($path)['extension'] ?? "unknown");
                $response->setHeader("Content-Type", ZMConfig::get("file_header")[$exp] ?? "application/octet-stream");
                $response->end(file_get_contents($path));
                return true;
            }
        }
        Console::info("[404] " . $uri);
        self::responseCodePage($response, 404);
        return true;
    }

    public static function responseCodePage($response, $code) {
        $response->status($code);
        $response->end(self::getHttpCodePage($code));
    }
}
