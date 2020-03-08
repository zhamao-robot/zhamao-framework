<?php


namespace ZM\Event\CQ;


use Framework\ZMBuf;
use ZM\Annotation\CQ\CQAfter;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQRequest;
use ZM\Connection\CQConnection;
use ZM\Exception\WaitTimeoutException;
use ZM\ModBase;
use ZM\ModHandleType;

class RequestEvent
{
    private $data;
    /** @var CQConnection */
    private $connection;
    private $circle;

    public function __construct($data, $connection, $circle = 0) {
        $this->data = $data;
        $this->connection = $connection;
        $this->circle = $circle;
    }

    public function onBefore() {
        foreach (ZMBuf::$events[CQBefore::class]["request"] ?? [] as $v) {
            $c = $v->class;
            /** @var CQRequest $v */
            $class = new $c([
                "data" => $this->data,
                "connection" => $this->connection
            ], ModHandleType::CQ_REQUEST);
            $r = call_user_func_array([$class, $v->method], []);
            if (!$r || $class->block_continue) return false;
        }
        return true;
    }

    /** @noinspection PhpRedundantCatchClauseInspection */
    public function onActivate() {
        try {
            /** @var ModBase[] $obj */
            $obj = [];
            foreach (ZMBuf::$events[CQRequest::class] ?? [] as $v) {
                /** @var CQRequest $v */
                if (
                    ($v->request_type == '' || ($v->request_type != '' && $v->request_type == $this->data["request_type"])) &&
                    ($v->sub_type == 0 || ($v->sub_type != 0 && $v->sub_type == $this->data["sub_type"])) &&
                    ($v->user_id == 0 || ($v->user_id != 0 && $v->user_id == ($this->data["user_id"] ?? 0))) &&
                    ($v->comment == 0 || ($v->comment != 0 && $v->comment == ($this->data["comment"] ?? 0)))) {
                    $c = $v->class;
                    if (!isset($obj[$c]))
                        $obj[$c] = new $c([
                            "data" => $this->data,
                            "connection" => $this->connection
                        ], ModHandleType::CQ_REQUEST);
                    $r = call_user_func([$obj[$c], $v->method]);
                    if (is_string($r)) $obj[$c]->reply($r);
                    if ($obj[$c]->block_continue) return;
                }
            }
        } catch (WaitTimeoutException $e) {
            $e->module->finalReply($e->getMessage());
        }
    }

    public function onAfter() {
        foreach (ZMBuf::$events[CQAfter::class]["request"] ?? [] as $v) {
            $c = $v->class;
            $class = new $c([
                "data" => $this->data,
                "connection" => $this->connection
            ], ModHandleType::CQ_REQUEST);
            $r = call_user_func_array([$class, $v->method], []);
            if (!$r || $class->block_continue) return false;
        }
        return true;
    }
}