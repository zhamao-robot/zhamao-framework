<?php

declare(strict_types=1);

namespace ZM\Utils;

use OneBot\Http\HttpFactory;
use OneBot\Http\ServerRequest;
use OneBot\Http\Stream;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use ZM\Config\ZMConfig;
use ZM\Exception\ConfigException;
use ZM\Store\FileSystem;

/**
 * Http 工具类
 */
class HttpUtil
{
    /**
     * @var RouteCollection
     */
    private static $routes;

    /**
     * 解析 Uri，用于匹配路由用的
     * 返回值为状态
     * 第二个参数为路由节点
     * 第三个参数为动态路由节点中匹配到的参数列表
     *
     * @param mixed $node
     * @param mixed $params
     */
    public static function parseUri(ServerRequest $request, &$node, &$params): int
    {
        // 建立上下文，设置当前请求的方法
        $context = new RequestContext();
        $context->setMethod($request->getMethod());

        try {
            // 使用UrlMatcher进行匹配Url
            $matcher = new UrlMatcher(static::getRouteCollection(), $context);
            $matched = $matcher->match($request->getUri()->getPath());
        } catch (ResourceNotFoundException $e) {
            // 路由找不到会抛出异常，我们不需要这个异常，转换为状态码
            return ZM_ERR_ROUTE_NOT_FOUND;
        } catch (MethodNotAllowedException $e) {
            // 路由匹配到了，但该路由不能使用该方法，所以返回状态码（路由不允许）
            return ZM_ERR_ROUTE_METHOD_NOT_ALLOWED;
        }
        // 匹配到的时候，matched不为空
        if (!empty($matched)) {
            $node = [
                'route' => static::getRouteCollection()->get($matched['_route'])->getPath(),
                'class' => $matched['_class'],
                'method' => $matched['_method'],
                'request_method' => $request->getMethod(),
            ];
            unset($matched['_class'], $matched['_method']);
            $params = $matched;
            // 返回成功的状态码
            return ZM_ERR_NONE;
        }
        // 返回没有匹配到的状态码
        return ZM_ERR_ROUTE_NOT_FOUND;
    }

    /**
     * 解析返回静态文件
     *
     * @params string $uri 路由地址
     * @params string $settings 动态传入的配置模式
     * @throws ConfigException
     */
    public static function handleStaticPage(string $uri, array $settings = []): ResponseInterface
    {
        // 确定根目录
        $base_dir = $settings['document_root'] ?? ZMConfig::get('global.file_server.document_root');
        // 将相对路径转换为绝对路径
        if (FileSystem::isRelativePath($base_dir)) {
            $base_dir = SOURCE_ROOT_DIR . '/' . $base_dir;
        }
        // 支持默认缺省搜索的文件名（如index.html）
        $base_index = $settings['document_index'] ?? ZMConfig::get('global.file_server.document_index');
        if (is_string($base_index)) {
            $base_index = [$base_index];
        }
        $path = realpath($base_dir . urldecode($uri));
        if ($path !== false) {
            // 安全问题，防止目录穿越，只能囚禁到规定的 Web 根目录下获取文件
            $work = realpath($base_dir) . '/';
            if (strpos($path, $work) !== 0) {
                logger()->info('[403] ' . $uri);
                return static::handleHttpCodePage(403);
            }
            // 如果路径是文件夹的话，如果结尾没有 /，则自动302补充，和传统的Nginx效果相同
            if (is_dir($path)) {
                if (mb_substr($uri, -1, 1) != '/') {
                    logger()->info('[302] ' . $uri);
                    return HttpFactory::getInstance()->createResponse(302, null, ['Location' => $uri . '/']);
                }
                // 如果结尾有 /，那么就根据默认搜索的文件名进行搜索文件是否存在，存在则直接返回对应文件
                foreach ($base_index as $vp) {
                    if (is_file($path . '/' . $vp)) {
                        logger()->info('[200] ' . $uri);
                        $exp = strtolower(pathinfo($path . $vp)['extension'] ?? 'unknown');
                        return HttpFactory::getInstance()->createResponse()
                            ->withAddedHeader('Content-Type', ZMConfig::get('file_header')[$exp] ?? 'application/octet-stream')
                            ->withBody(HttpFactory::getInstance()->createStream(file_get_contents($path . '/' . $vp)));
                    }
                }
            } elseif (is_file($path)) {
                // 如果文件存在，则直接返回文件内容
                logger()->info('[200] ' . $uri);
                $exp = strtolower(pathinfo($path)['extension'] ?? 'unknown');
                return HttpFactory::getInstance()->createResponse()
                    ->withAddedHeader('Content-Type', ZMConfig::get('file_header')[$exp] ?? 'application/octet-stream')
                    ->withBody(HttpFactory::getInstance()->createStream(file_get_contents($path)));
            }
        }
        // 否则最终肯定只能返回 404 了
        logger()->info('[404] ' . $uri);
        return static::handleHttpCodePage(404);
    }

    /**
     * 自动寻找默认的 HTTP Code 页面
     *
     * @throws ConfigException
     */
    public static function handleHttpCodePage(int $code): ResponseInterface
    {
        // 获取有没有规定 code page
        $code_page = ZMConfig::get('global.file_server.document_code_page')[$code] ?? null;
        if ($code_page !== null && !file_exists((ZMConfig::get('global.file_server.document_root') ?? '/not/exist/') . '/' . $code_page)) {
            $code_page = null;
        }
        if ($code_page === null) {
            return HttpFactory::getInstance()->createResponse($code);
        }
        return HttpFactory::getInstance()->createResponse($code, null, [], file_get_contents(ZMConfig::get('global.file_server.document_root') . '/' . $code_page));
    }

    /**
     * 快速创建一个 JSON 格式的 HTTP 响应
     *
     * @param array $data      数据
     * @param int   $http_code HTTP 状态码
     * @param int   $json_flag JSON 编码时传入的flag
     */
    public static function createJsonResponse(array $data, int $http_code = 200, int $json_flag = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): ResponseInterface
    {
        return HttpFactory::getInstance()->createResponse($http_code)
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(Stream::create(json_encode($data, $json_flag)));
    }

    public static function getRouteCollection(): RouteCollection
    {
        if (self::$routes === null) {
            self::$routes = new RouteCollection();
        }
        return self::$routes;
    }
}
