<?php


namespace ZM\Event\CQ;


use Framework\ZMBuf;
use ZM\Annotation\CQ\CQAfter;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQNotice;
use ZM\Connection\CQConnection;
use ZM\Exception\WaitTimeoutException;
use ZM\ModBase;
use ZM\ModHandleType;

class NoticeEvent
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
        foreach (ZMBuf::$events[CQBefore::class]["notice"] ?? [] as $v) {
            $c = $v->class;
            /** @var CQNotice $v */
            $class = new $c([
                "data" => $this->data,
                "connection" => $this->connection
            ], ModHandleType::CQ_NOTICE);
            $r = call_user_func_array([$class, $v->method], []);
            if (!$r || $class->block_continue) return false;
        }
        return true;
    }

    public function onActivate() {
        try {
            /** @var ModBase[] $obj */
            $obj = [];
            foreach (ZMBuf::$events[CQNotice::class] ?? [] as $v) {
                /** @var CQNotice $v */
                if (
                    ($v->notice_type == '' || ($v->notice_type != '' && $v->notice_type == $this->data["notice_type"])) &&
                    ($v->sub_type == 0 || ($v->sub_type != 0 && $v->sub_type == $this->data["sub_type"])) &&
                    ($v->group_id == 0 || ($v->group_id != 0 && $v->group_id == ($this->data["group_id"] ?? 0))) &&
                    ($v->operator_id == 0 || ($v->operator_id != 0 && $v->operator_id == ($this->data["operator_id"] ?? 0)))) {
                    $c = $v->class;
                    if (!isset($obj[$c]))
                        $obj[$c] = new $c([
                            "data" => $this->data,
                            "connection" => $this->connection
                        ], ModHandleType::CQ_NOTICE);
                    $r = call_user_func([$obj[$c], $v->method]);
                    if (is_string($r)) $obj[$c]->reply($r);
                    if ($obj[$c]->block_continue) return;
                }
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (WaitTimeoutException $e) {
            $e->module->finalReply($e->getMessage());
        }
    }

    public function onAfter() {
        foreach (ZMBuf::$events[CQAfter::class]["notice"] ?? [] as $v) {
            $c = $v->class;
            $class = new $c([
                "data" => $this->data,
                "connection" => $this->connection
            ], ModHandleType::CQ_NOTICE);
            $r = call_user_func_array([$class, $v->method], []);
            if (!$r || $class->block_continue) return false;
        }
        return true;
    }
}