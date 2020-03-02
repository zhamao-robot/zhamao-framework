<?php


namespace ZM\Http;


class Response
{

    public $fd = 0;

    public $socket = null;

    public $header = null;

    public $cookie = null;

    public $trailer = null;
    /**
     * @var \Swoole\Http\Response
     */
    private $response;
    private $is_end = false;

    public function __construct(\Swoole\Http\Response $response) {
        $this->response = $response;
        $this->fd = $response->fd;
        $this->socket = $response->socket;
        $this->header = $response->header;
        $this->cookie = $response->cookie;
        if (isset($response->trailer))
            $this->trailer = $response->trailer;
    }

    /**
     * @return mixed
     */
    public function initHeader() {
        return $this->response->initHeader();
    }

    /**
     * @param $name
     * @param $value
     * @param $expires
     * @param $path
     * @param $domain
     * @param $secure
     * @param $httponly
     * @param $samesite
     * @return mixed
     */
    public function cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null) {
        return $this->response->rawcookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite);
    }

    /**
     * @param $name
     * @param $value
     * @param $expires
     * @param $path
     * @param $domain
     * @param $secure
     * @param $httponly
     * @param $samesite
     * @return mixed
     */
    public function setCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null) {
        return $this->response->setCookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite);
    }

    /**
     * @param $name
     * @param $value
     * @param $expires
     * @param $path
     * @param $domain
     * @param $secure
     * @param $httponly
     * @param $samesite
     * @return mixed
     */
    public function rawcookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null, $samesite = null) {
        return $this->response->rawcookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite);
    }

    /**
     * @param $http_code
     * @param $reason
     * @return mixed
     */
    public function status($http_code, $reason = null) {
        return $this->response->status($http_code, $reason);
    }

    /**
     * @param $http_code
     * @param $reason
     * @return mixed
     */
    public function setStatusCode($http_code, $reason = null) {
        return $this->response->setStatusCode($http_code, $reason);
    }

    /**
     * @param $key
     * @param $value
     * @param $ucwords
     * @return mixed
     */
    public function header($key, $value, $ucwords = null) {
        return $this->response->header($key, $value, $ucwords);
    }

    /**
     * @param $key
     * @param $value
     * @param $ucwords
     * @return mixed
     */
    public function setHeader($key, $value, $ucwords = null) {
        return $this->response->setHeader($key, $value, $ucwords);
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function trailer($key, $value) {
        return $this->response->trailer($key, $value);
    }

    /**
     * @return mixed
     */
    public function ping() {
        return $this->response->ping();
    }

    /**
     * @param $content
     * @return mixed
     */
    public function write($content) {
        return $this->response->write($content);
    }

    /**
     * @param $content
     * @return mixed
     */
    public function end($content = null) {
        $this->is_end = true;
        return $this->response->end($content);
    }

    public function isEnd() { return $this->is_end; }

    /**
     * @param $filename
     * @param $offset
     * @param $length
     * @return mixed
     */
    public function sendfile($filename, $offset = null, $length = null) {
        return $this->response->sendfile($filename, $offset, $length);
    }

    /**
     * @param $location
     * @param $http_code
     * @return mixed
     */
    public function redirect($location, $http_code = null) {
        return $this->redirect($location, $http_code);
    }

    /**
     * @return mixed
     */
    public function detach() {
        return $this->response->detach();
    }

    /**
     * @param $fd
     * @return mixed
     */
    public static function create($fd) {
        return \Swoole\Http\Response::create($fd);
    }

    /**
     * @return mixed
     */
    public function upgrade() {
        return $this->response->upgrade();
    }

    /**
     * @param $data
     * @param null $opcode
     * @param null $flags
     * @return mixed
     */
    public function push($data, $opcode = null, $flags = null) {
        return $this->response->push($data, $opcode, $flags);
    }

    /**
     * @return mixed
     */
    public function recv() {
        return $this->response->recv();
    }

    /**
     * @return mixed
     */
    public function close() {
        return $this->response->close();
    }

    public function __destruct() {
    }


}