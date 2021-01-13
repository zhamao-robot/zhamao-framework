<?php


namespace ZM\Utils;


use Co;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Http\Response;
use ZM\Http\RouteManager;

class HttpUtil
{
    public static function parseUri($request, $response, $uri, &$node, &$params) {
        $context = new RequestContext();
        $context->setMethod($request->server['request_method']);

        try {
            $matcher = new UrlMatcher(RouteManager::$routes ?? new RouteCollection(), $context);
            $matched = $matcher->match($uri);
        } catch (ResourceNotFoundException $e) {
            if (ZMConfig::get("global", "static_file_server")["status"]) {
                HttpUtil::handleStaticPage($request->server["request_uri"], $response);
                return null;
            }
            $matched = null;
        } catch (MethodNotAllowedException $e) {
            $matched = null;
        }
        if ($matched !== null) {
            $node = [
                "route" => RouteManager::$routes->get($matched["_route"])->getPath(),
                "class" => $matched["_class"],
                "method" => $matched["_method"],
                "request_method" => $request->server['request_method']
            ];
            unset($matched["_class"], $matched["_method"]);
            $params = $matched;
            return true;
        } else {
            return false;
        }
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
                if (mb_substr($uri, -1, 1) != "/") {
                    Console::info("[302] " . $uri);
                    $response->redirect($uri . "/", 302);
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
