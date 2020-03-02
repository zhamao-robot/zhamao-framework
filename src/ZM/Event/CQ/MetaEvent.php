<?php


namespace ZM\Event\CQ;


use Framework\ZMBuf;
use ZM\Annotation\CQ\CQBefore;
use ZM\Annotation\CQ\CQMetaEvent;
use ZM\Connection\ConnectionManager;
use ZM\Exception\WaitTimeoutException;
use ZM\ModBase;
use ZM\ModHandleType;

class MetaEvent
{
    private $data;
    /** @var \ZM\Event\Swoole\MessageEvent */
    private $swoole_event;
    private $circle;

    public function __construct($data, \ZM\Event\Swoole\MessageEvent $event, $circle = 0) {
        $this->data = $data;
        $this->swoole_event = $event;
        $this->circle = $circle;
    }

    public function onBefore() {
        foreach (ZMBuf::$events[CQBefore::class][CQMetaEvent::class] ?? [] as $v) {
            $c = $v->class;
            /** @var CQMetaEvent $v */
            $class = new $c([
                "data" => $this->data,
                "frame" => $this->swoole_event->frame,
                "server" => $this->swoole_event->server,
                "connection" => ConnectionManager::get($this->swoole_event->frame->fd)
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
                            "frame" => $this->swoole_event->frame,
                            "server" => $this->swoole_event->server,
                            "connection" => ConnectionManager::get($this->swoole_event->frame->fd)
                        ], ModHandleType::CQ_META_EVENT);
                    $r = call_user_func([$obj[$c], $v->method]);
                    if (is_string($r)) $obj[$c]->reply($r);
                    if ($obj[$c]->block_continue) return;
                }
            }
        } catch (WaitTimeoutException $e) {
            $e->module->finalReply($e->getMessage());
        }
    }
}