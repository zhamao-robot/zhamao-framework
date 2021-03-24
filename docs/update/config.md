# 配置文件变更记录

这里将会记录各个主版本的框架升级后，涉及 `global.php` 的更新日志，你可以根据这里描述的内容与你的旧配置文件进行合并。

## v2.4.0 (build 400)
- 调整 `$config['modules']['onebot']` 配置项到 `$config['onebot']`，旧版本的此段会向下兼容，建议更新，
- 新增 `$config['remote_terminal']` 远程终端的配置项，新增此段即可。

更新部分：
```php
/** 机器人解析模块，关闭后无法使用如CQCommand等注解(上面的modules即将废弃) */
$config['onebot'] = [
    'status' => true,
    'single_bot_mode' => false,
    'message_level' => 99999
];

/** 一个远程简易终端，使用nc直接连接即可，但是不建议开放host为0.0.0.0(远程连接) */
$config['remote_terminal'] = [
    'status' => false,
    'host' => '127.0.0.1',
    'port' => 20002,
    'token' => ''
];
```