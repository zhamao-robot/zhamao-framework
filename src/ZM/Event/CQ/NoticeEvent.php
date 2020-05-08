<?php


namespace ZM\Event\CQ;


use Doctrine\Common\Annotations\AnnotationException;
use Framework\ZMBuf;
use ZM\Annotation\CQ\CQAfter;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQNotice;
use ZM\Connection\CQConnection;
use ZM\Event\EventHandler;
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

    /**
     * @return bool
     * @throws AnnotationException
     */
    public function onBefore() {
        foreach (ZMBuf::$events[CQBefore::class]["notice"] ?? [] as $v) {
            $c = $v->class;
            EventHandler::callWithMiddleware(
                $c,
                $v->method,
                ["data" => context()->getData(), "connection" => $this->connection],
                [],
                function ($r) {
                    if(!$r) context()->setCache("block_continue", true);
                }
            );
            if(context()->getCache("block_continue") === true) return false;
        }
        return true;
    }

    /**
     * @throws AnnotationException
     */
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
                    EventHandler::callWithMiddleware($obj[$c],$v->method, [], [], function($r) {
                        if (is_string($r)) context()->reply($r);
                    });
                    if (context()->getCache("block_continue") === true) return;
                }
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (WaitTimeoutException $e) {
            $e->module->finalReply($e->getMessage());
        }
    }

    /**
     * @return bool
     * @throws AnnotationException
     */
    public function onAfter() {
        foreach (ZMBuf::$events[CQAfter::class]["notice"] ?? [] as $v) {
            $c = $v->class;
            EventHandler::callWithMiddleware(
                $c,
                $v->method,
                ["data" => context()->getData(), "connection" => $this->connection],
                [],
                function ($r) {
                    if(!$r) context()->setCache("block_continue", true);
                }
            );
            if(context()->getCache("block_continue") === true) return false;
        }
        return true;
    }
}
