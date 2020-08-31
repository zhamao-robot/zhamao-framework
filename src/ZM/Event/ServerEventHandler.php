<?php


namespace ZM\Event;


use Co;
use Doctrine\Common\Annotations\AnnotationException;
use ZM\Console\Console;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\WebSocket\Frame;
use ZM\Annotation\Swoole\OnEvent;
use ZM\Http\Response;
use ZM\Store\ZMBuf;

class ServerEventHandler
{
    /**
     * @OnEvent("WorkerStart")
     * @param Server $server
     * @param $worker_id
     * @throws AnnotationException
     */
    public function onWorkerStart(Server $server, $worker_id) {
        if ($server->taskworker === false) {
            EventHandler::callSwooleEvent("WorkerStart", $server, $worker_id);
        } else {
            ob_start();
            //AnnotationParser::registerMods();
            ob_get_clean();
        }
    }

    /**
     * @OnEvent("message")
     * @param $server
     * @param Frame $frame
     * @throws AnnotationException
     */
    public function onMessage($server, Frame $frame) {
        if ($frame->fd !== ZMBuf::get("terminal_fd"))
            Console::debug("Calling Swoole \"message\" from fd=" . $frame->fd);
        EventHandler::callSwooleEvent("message", $server, $frame);
    }

    /**
     * @OnEvent("request")
     * @param $request
     * @param $response
     * @throws AnnotationException
     */
    public function onRequest($request, $response) {
        $response = new Response($response);
        Console::debug("Receiving Http request event, cid=" . Co::getCid());
        EventHandler::callSwooleEvent("request", $request, $response);
    }

    /**
     * @OnEvent("open")
     * @param $server
     * @param Request $request
     * @throws AnnotationException
     */
    public function onOpen($server, Request $request) {
        Console::debug("Calling Swoole \"open\" event from fd=" . $request->fd);
        EventHandler::callSwooleEvent("open", $server, $request);
    }

    /**
     * @OnEvent("close")
     * @param $server
     * @param $fd
     * @throws AnnotationException
     */
    public function onClose($server, $fd) {
        Console::debug("Calling Swoole \"close\" event from fd=" . $fd);
        EventHandler::callSwooleEvent("close", $server, $fd);
    }
}
