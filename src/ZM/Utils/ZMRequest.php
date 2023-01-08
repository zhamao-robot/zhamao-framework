<?php

declare(strict_types=1);

namespace ZM\Utils;

use OneBot\Util\Singleton;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use ZM\Framework;

class ZMRequest
{
    use Singleton;

    /**
     * 快速发起一个 GET 请求
     *
     * @param  string|\Stringable|UriInterface $url       请求地址
     * @param  array                           $headers   请求头
     * @param  array                           $config    传入参数
     * @param  bool                            $only_body 是否只返回 Response 的 body 部分，默认为 True
     * @return bool|ResponseInterface|string   返回 False 代表请求失败，返回 string 为仅 Body 的内容，返回 Response 接口对象表明是回包
     */
    public static function get(string|UriInterface|\Stringable $url, array $headers = [], array $config = [], bool $only_body = true): bool|ResponseInterface|string
    {
        $socket = Framework::getInstance()->getDriver()->createHttpClientSocket(array_merge_recursive([
            'url' => ($url instanceof UriInterface ? $url->__toString() : $url),
        ], $config));
        $socket->withoutAsync();
        $obj = $socket->get($headers, function (ResponseInterface $response) { return $response; }, function () { return false; });
        if ($obj instanceof ResponseInterface) {
            if ($obj->getStatusCode() !== 200 && $only_body) {
                return false;
            }
            if (!$only_body) {
                return $obj;
            }
            return $obj->getBody()->getContents();
        }
        return $obj;
    }

    /**
     * 快速发起一个 POST 请求
     *
     * @param  string|\Stringable|UriInterface $url       请求地址
     * @param  array                           $header    请求头
     * @param  mixed                           $data      请求数据，当传入了一个可以 Json 化的对象时，自动 json_encode，其他情况须传入可字符串化的变量
     * @param  array                           $config    传入参数
     * @param  bool                            $only_body 是否只返回 Response 的 body 部分，默认为 True
     * @return bool|ResponseInterface|string   返回 False 代表请求失败，返回 string 为仅 Body 的内容，返回 Response 接口对象表明是回包
     */
    public static function post(string|UriInterface|\Stringable $url, array $header, mixed $data, array $config = [], bool $only_body = true): bool|ResponseInterface|string
    {
        $socket = Framework::getInstance()->getDriver()->createHttpClientSocket(array_merge_recursive([
            'url' => ($url instanceof UriInterface ? $url->__toString() : $url),
        ], $config));
        $socket->withoutAsync();
        if (is_array($data)) {
            $data = http_build_query($data);
            $header['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        $obj = $socket->post($data, $header, fn (ResponseInterface $response) => $response, fn () => false);
        if ($obj instanceof ResponseInterface) {
            if ($obj->getStatusCode() !== 200 && $only_body) {
                return false;
            }
            if (!$only_body) {
                return $obj;
            }
            return $obj->getBody()->getContents();
        }
        return $obj;
    }
}
