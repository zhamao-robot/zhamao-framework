<?php


namespace ZM\Event\CQ;


use Framework\ZMBuf;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQMetaEvent;
use ZM\Connection\ConnectionManager;
use ZM\Connection\CQConnection;
use ZM\Exception\WaitTimeoutException;
use ZM\ModBase;
use ZM\ModHandleType;

class MetaEvent
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
        foreach (ZMBuf::$events[CQBefore::class]["meta_event"] ?? [] as $v) {
            $c = $v->class;
            /** @var CQMetaEvent $v */
            $class = new $c([
                "data" => $this->data,
                "connection" => $this->connection
            ], ModHandleType::CQ_META_EVENT);
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
            foreach (ZMBuf::$events[CQMetaEvent::class] ?? [] as $v) {
                /** @var CQMetaEvent $v */
                if (
                    ($v->meta_event_type == '' || ($v->meta_event_type != '' && $v->meta_event_type == $this->data["meta_event_type"])) &&
                    ($v->sub_type == 0 || ($v->sub_type != 0 && $v->sub_type == $this->data["sub_type"]))) {
                    $c = $v->class;
                    if (!isset($obj[$c]))
                        $obj[$c] = new $c([
                            "data" => $this->data,
                            "connection" => $this->connection
                        ], ModHandleType::CQ_META_EVENT);
                    $r = call_user_func([$obj[$c], $v->method]);
                    if (is_string($r)) $obj[$c]->reply($r);
                    if (context()->getCache("block_continue") === true) return;
                }
            }
        } catch (WaitTimeoutException $e) {
            $e->module->finalReply($e->getMessage());
        }
    }
}
