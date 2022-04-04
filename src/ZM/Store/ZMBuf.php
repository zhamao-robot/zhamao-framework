<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: jerry
 * Date: 2018/2/25
 * Time: 下午11:11
 */

namespace ZM\Store;

use ZM\Context\ContextInterface;

class ZMBuf
{
    /**
     * 注册的事件
     *
     * @deprecated 不再使用
     *
     * @var array
     */
    public static $events = [];

    /**
     * 全局单例容器
     *
     * @var array
     */
    public static $instance = [];

    /**
     * 上下文容器
     *
     * @var array<int, ContextInterface>
     */
    public static $context_class = [];

    /**
     * 终端输入流？
     *
     * 目前等用于 STDIN
     *
     * @var resource
     */
    public static $terminal;
}
