<?php


namespace ZM\Event\CQ;


use Doctrine\Common\Annotations\AnnotationException;
use Framework\ZMBuf;
use ZM\Annotation\CQ\CQAfter;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQRequest;
use ZM\Connection\CQConnection;
use ZM\Event\EventHandler;
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

    /**
     * @return bool
     * @throws AnnotationException
     */
    public function onBefore() {
        foreach (ZMBuf::$events[CQBefore::class]["request"] ?? [] as $v) {
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
                    EventHandler::callWithMiddleware($obj[$c],$v->method, [], [], function($r) {
                        if (is_string($r)) context()->reply($r);
                    });
                    if (context()->getCache("block_continue") === true) return;
                }
            }
        } catch (WaitTimeoutException $e) {
            $e->module->finalReply($e->getMessage());
        }
    }

    /**
     * @return bool
     * @throws AnnotationException
     */
    public function onAfter() {
        foreach (ZMBuf::$events[CQAfter::class]["request"] ?? [] as $v) {
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
