# 路由事件

<aside>
🛰️ 此页面下的所有注解命名空间为 `ZM\Annotation\Http`

</aside>

## Controller

对同一类下的路由进行修饰，只可在类上使用。

| 参数名称 | 允许值 | 用途 | 默认 |
| --- | --- | --- | --- |
| prefix | string | 路由前缀，应用到类下的所有路由 | 必填 |

## Route

路由事件，当对应的路由收到请求时触发。

| 参数名称 | 允许值 | 用途 | 默认 |
| --- | --- | --- | --- |
| route | string | 路由 | 必填 |
| name | string | 路由名称 | “” |
| request_method | array<string> | 允许的请求方法 | [’GET’, ‘POST’] |
| params | array<string, string> | 路由参数 | [] |
