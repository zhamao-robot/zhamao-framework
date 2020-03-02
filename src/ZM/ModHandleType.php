<?php


namespace ZM;


class ModHandleType
{
    const CQ_MESSAGE = 0;
    const CQ_REQUEST = 1;
    const CQ_NOTICE = 2;
    const CQ_META_EVENT = 3;

    const SWOOLE_OPEN = 4;
    const SWOOLE_CLOSE = 5;
    const SWOOLE_MESSAGE = 6;
    const SWOOLE_REQUEST = 7;
    const SWOOLE_WORKER_START = 8;
    const SWOOLE_WORKER_STOP = 9;
}