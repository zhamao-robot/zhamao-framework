# 框架事件

<aside>
🛰️ 此页面下的所有注解命名空间为 `ZM\Annotation\Framework`

</aside>

## BindEvent

相对底层的事件绑定，支持绑定所有透过框架分发的事件。

| 参数名称        | 允许值    | 用途            | 默认  |
|-------------|--------|---------------|-----|
| event_class | string | 时间名           | 必填  |
| level       | int    | 事件优先级（越大越先执行） | 800 |

## Init

在 Worker 进程初始化时触发，用于进行 Worker 初始化。

| 参数名称   | 允许值                     | 用途                                | 默认  |
|--------|-------------------------|-----------------------------------|-----|
| worker | int 由 0 至 (最大Worker数-1) | 限定执行的 Worker 进程，-1 为在所有 Worker 执行 | 0   |

## Setup

在框架初始化时触发，在主进程执行，不可使用协程相关功能。

可用于改变所有进程的设置，相关更改会随着进程创建应用到所有 Worker 和 Manager 进程。

*没有参数*