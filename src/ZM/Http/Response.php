<?php

/**
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace ZM\Http;

class Response
{
    public $fd = 0;

    public $socket;

    public $header;

    public $cookie;

    public $trailer;

    /**
     * @var \Swoole\Http\Response
     */
    private $response;

    private $is_end = false;

    private $status_code;

    public function __construct(\Swoole\Http\Response $response)
    {
        $this->response = $response;
        $this->fd = $response->fd;
        $this->socket = $response->socket;
        $this->header = $response->header;
        $this->cookie = $response->cookie;
        if (isset($response->trailer)) {
            $this->trailer = $response->trailer;
        }
    }

    public function __destruct()
    {
    }

    /**
     * @return mixed
     */
    public function initHeader()
    {
        return $this->response->initHeader();
    }

    /**
     * @param $name
     * @param  mixed ...$params
     * @return mixed
     */
    public function cookie($name, ...$params)
    {
        return empty($params) ? $this->response->rawcookie($name) : $this->response->rawcookie($name, ...$params);
    }

    /**
     * @param  mixed ...$params
     * @param  mixed $name
     * @return mixed
     */
    public function setCookie($name, ...$params)
    {
        return empty($params) ? $this->response->rawcookie($name) : $this->response->setCookie($name, ...$params);
    }

    /**
     * @param  mixed ...$params
     * @param  mixed $name
     * @return mixed
     */
    public function rawcookie($name, ...$params)
    {
        return empty($params) ? $this->response->rawcookie($name) : $this->response->rawcookie($name, ...$params);
    }

    /**
     * @param $http_code
     * @param $reason
     * @return mixed
     */
    public function status($http_code, ...$params)
    {
        $this->status_code = $http_code;
        if (!$this->is_end) {
            return empty($params) ? $this->response->status($http_code) : $this->response->status($http_code, ...$params);
        }
        return false;
    }

    public function getStatusCode()
    {
        return $this->status_code ?? 200;
    }

    /**
     * @param $http_code
     * @param $reason
     * @return mixed
     */
    public function setStatusCode($http_code, ...$params)
    {
        if (!$this->is_end) {
            return empty($params) ? $this->response->status($http_code) : $this->response->status($http_code, ...$params);
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @param $ucwords
     * @return mixed
     */
    public function header($key, $value, $ucwords = null)
    {
        if (!$this->is_end) {
            return $ucwords === null ? $this->response->header($key, $value) : $this->response->header($key, $value, $ucwords);
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @param $ucwords
     * @return mixed
     */
    public function setHeader($key, $value, $ucwords = null)
    {
        if (!$this->is_end) {
            return $ucwords === null ? $this->response->setHeader($key, $value) : $this->response->setHeader($key, $value, $ucwords);
        }
        return false;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function trailer($key, $value)
    {
        return $this->response->trailer($key, $value);
    }

    /**
     * @return mixed
     */
    public function ping()
    {
        return $this->response->ping();
    }

    /**
     * @param $content
     * @return mixed
     */
    public function write($content)
    {
        return $this->response->write($content);
    }

    /**
     * @param $content
     * @return mixed
     */
    public function end($content = null)
    {
        if (!$this->is_end) {
            $this->is_end = true;
            return $this->response->end($content);
        }
        return false;
    }

    public function isEnd()
    {
        return $this->is_end;
    }

    public function endWithStatus($status_code = 200, $content = null)
    {
        $this->status($status_code);
        $this->end($content);
    }

    /**
     * @param $filename
     * @param $offset
     * @param $length
     * @return mixed
     */
    public function sendfile($filename, $offset = null, $length = null)
    {
        return $this->response->sendfile($filename, $offset, $length);
    }

    /**
     * @param $location
     * @param $http_code
     * @return mixed
     */
    public function redirect($location, $http_code = null)
    {
        $this->is_end = true;
        return $this->response->redirect($location, $http_code);
    }

    /**
     * @return mixed
     */
    public function detach()
    {
        return $this->response->detach();
    }

    /**
     * @param $fd
     * @return mixed
     */
    public static function create($fd)
    {
        return \Swoole\Http\Response::create($fd);
    }

    /**
     * @return mixed
     */
    public function upgrade()
    {
        return $this->response->upgrade();
    }

    /**
     * @param $data
     * @param  null  $opcode
     * @param  null  $flags
     * @return mixed
     */
    public function push($data, $opcode = null, $flags = null)
    {
        return $this->response->push($data, $opcode, $flags);
    }

    /**
     * @return mixed
     */
    public function recv()
    {
        return $this->response->recv();
    }

    /**
     * @return mixed
     */
    public function close()
    {
        return $this->response->close();
    }
}
