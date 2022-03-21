# 接入安全验证 - Token

为了保障安全，框架支持给接入的 WebSocket 连接验证 Token，如果不设置 Token 同时又将框架的端口暴露在公网将会非常危险。

炸毛框架兼容 OneBot 标准的机器人客户端，所以自带一个 Token 验证器。

关于 Access Token 方面的标准规范，请参考下面内容：

- [OneBot - 鉴权](https://github.com/howmanybots/onebot/blob/master/v11/specs/communication/authorization.md)
- [go-cqhttp - 配置](https://github.com/Mrs4s/go-cqhttp/blob/master/docs/config.md)

> 以 go-cqhttp 举例，如果要设置验证，则将 go-cqhttp 配置文件中的 `access_token` 项填入内容即可。

## 验证位置

框架对 Token 的验证是内置的，在事件 `open`（WebSocket 连接接入时）触发。

如果是兼容 OneBot 标准的客户端接入，则一切都是兼容的。

如果是自定义的其他 WebSocket 客户端也想接入框架，那么其他 WebSocket 客户端也需要进行相应的设置才能利用此 Token 验证。

如果验证成功（Token 符合要求）则分发事件 `@OnOpenEvent`，否则此事件不触发，同时断开 WebSocket 连接。

## 标准验证（字符串形式）

默认的情况下，在框架的全局配置文件 `global.php` 中，对配置项 `access_token` 填入与 OneBot 客户端相同的 `access_token` 即可实现鉴权。下面是一个最基本的和 go-cqhttp 设置鉴权配置：

go-cqhttp 的配置段：

```
// 访问密钥, 强烈推荐在公网的服务器设置
access_token: "emhhbWFvLXJvYm90"
```

框架的配置文件配置段：

```php
/** onebot连接约定的token */
$config["access_token"] = 'emhhbWFvLXJvYm90';
```

然后重启框架和 go-cqhttp 即可。（其他 OneBot 客户端同理）

## 自定义验证（Token 验证）

有些情况下，使用一个单一的字符串可能无法满足你对 Token 验证的安全需求，需要自定义一些判断模式才能满足，所以框架的 `access_token` 配置项支持动态的闭包函数自行编写判断逻辑，例如下面的一个例子，我可以让框架同时允许接入多个不同 token 的 WebSocket 连接：

```php
/** onebot连接约定的token */
$config["access_token"] = function($token){
    $allow = ['emhhbWFvLXJvYm90','aXMtdmVyeS1nb29k'];
    if (in_array($token, $allow)) return true;
    else return false;
};
```

## 自定义验证（open 事件）

当然，这里设置了自定义方式，其实你也可以在下一层的 `@OnOpenEvent` 注解事件中进行自定义内容和判断，具体见 `@OnOpenEvent` 的相关章节。
