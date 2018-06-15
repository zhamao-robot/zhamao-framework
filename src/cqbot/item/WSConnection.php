<?php
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/6/13
 * Time: 8:10 PM
 */

class WSConnection
{
    public $fd;

    /**
     * 0 = event连接
     * 1 = api连接
     * 默认为event连接，如果可以收到返回的get_status则标记为1
     * @var int
     */
    protected $type = 0;

    protected $server;

    /** @var WSConnection */
    protected $pair = null;

    protected $qq = "";

    public function __construct(swoole_websocket_server $server, $fd) {
        $this->server = $server;
        $this->fd = $fd;
        $this->manageType();
    }

    /**
     * 返回swoole server
     * @return swoole_websocket_server
     */
    public function getServer() {
        return $this->server;
    }

    /**
     * 返回本连接是什么类型的
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * 用来确认此连接是API还是event
     * 如果此fd连接是event，则不会返回任何信息，关于QQ的匹配，则会在接收入第一条消息后设置
     * 如果此连接是api，则此操作后，HTTP API会返回登录号的号码，如果返回了则标记此连接为api并记录这个api连接属于的QQ号
     */
    private function manageType() {
        $this->server->push($this->fd, '{"action":"get_login_info","echo":{"type":"handshake"}}');
    }

    /**
     * 返回此连接相关联的event连接，使用前需初始化完成
     * @return $this|null|WSConnection
     */
    public function getEventConnection() {
        switch ($this->type) {
            case 0:
                return $this;
            case 1:
                return $this->pair;
            default:
                return null;
        }
    }

    /**
     * 返回此链接相关联的api连接，使用前需初始化完成
     * @return $this|null|WSConnection
     */
    public function getApiConnection() {
        switch ($this->type) {
            case 0:
                return $this->pair;
            case 1:
                return $this;
            default:
                return null;
        }
    }

    /**
     * 检查此连接对应的QQ，此部分较为复杂，先留着
     * @param $qq
     */
    public function manageQQ($qq) {
        //TODO
    }

    /**
     * 返回此连接属于的QQ号
     * @return string
     */
    public function getQQ() {
        return $this->qq;
    }

    /**
     * 返回关联连接（experiment）
     * @return WSConnection
     */
    public function getPair() {
        return $this->pair;
    }

    /**
     * 设置关联连接
     * @param WSConnection $pair
     */
    public function setPair(WSConnection $pair) {
        $this->pair = $pair;
    }

    /**
     * @param string $qq
     */
    public function setQQ($qq) {
        $this->qq = $qq;
    }

    /**
     * @param int $type
     */
    public function setType(int $type) {
        $this->type = $type;
    }

    /**
     *
     */
    public function findSub() {
        if ($this->qq != "") {
            foreach (CQUtil::getConnections() as $fd => $cn) {
                if ($cn->getQQ() == $this->qq && $cn->getType() != $this->getType()) {
                    $this->setPair($cn);
                    $cn->setPair($this);
                }
            }
        }
    }
}