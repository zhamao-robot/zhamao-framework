# 快速上手 - 机器人篇

## 简介

看到这里，你已经完成了前面的环境部署，到了最关键的第一步了！

一切都安装成功后，你就已经做好了进行简单配置以运行一个最小的 **机器人问答模块** 的准备。

炸毛框架和机器人客户端是什么关系呢？炸毛框架就好比我们传统的一系列例如 Spring 框架、ThinkPHP 框架等，是服务端，而机器人客户端是一个 HTTP / WebSocket 客户端，时刻准备着连接到炸毛框架。

## 机器人客户端

开发之前请注意，**机器人客户端和框架相互独立！**故有关**机器人客户端**出现的问题请到对应机器人客户端开发者或 GitHub 项目中咨询和讨论，炸毛框架为对接机器人客户端的一个快速开发的框架。

机器人客户端是炸毛框架以外的程序或软件，目前炸毛框架支持的机器人客户端通信标准为 OneBot 标准（原 CQHTTP），只要你的机器人客户端是 OneBot 标准的，就可以和炸毛框架进行无缝对接。

OneBot 机器人部分的选择详情见 [OneBot 实例](/guide/OneBot实例/)。

这里以炸毛框架开发过程中使用的 [go-cqhttp](https://github.com/Mrs4s/go-cqhttp) 来举例进行第一个机器人的配置工作。

简要步骤描述为：

1. 下载 go-cqhttp 对应平台的 [release 文件](https://github.com/Mrs4s/go-cqhttp/releases)
2. 双击 exe 文件或者使用 `./go-cqhttp` 启动
3. 生成默认配置文件并修改默认配置

::: warning 注意

由于 go-cqhttp 项目还处于开发期，而且配置文件格式也发生了多次变化，但大体内容没有变（比如编写此文档时发布的版本中配置文件格式变成了 `hjson` 取代了原来的 `json`。

:::

config.yml（最新格式）：

```yaml {2,3,78,83}
account: # 账号相关
  uin: 1233456 # QQ账号
  password: '' # 密码为空时使用扫码登录
  encrypt: false  # 是否开启密码加密
  status: 0      # 在线状态 请参考 https://github.com/Mrs4s/go-cqhttp/blob/dev/docs/config.md#在线状态
  relogin: # 重连设置
    delay: 3   # 首次重连延迟, 单位秒
    interval: 3   # 重连间隔
    max-times: 0  # 最大重连次数, 0为无限制

  # 是否使用服务器下发的新地址进行重连
  # 注意, 此设置可能导致在海外服务器上连接情况更差
  use-sso-address: true

heartbeat:
# 心跳频率, 单位秒
# -1 为关闭心跳
interval: 5

message:
  # 上报数据类型
  # 可选: string,array
  post-format: string
  # 是否忽略无效的CQ码, 如果为假将原样发送
  ignore-invalid-cqcode: false
  # 是否强制分片发送消息
  # 分片发送将会带来更快的速度
  # 但是兼容性会有些问题
  force-fragment: false
  # 是否将url分片发送
  fix-url: false
  # 下载图片等请求网络代理
  proxy-rewrite: ''
  # 是否上报自身消息
  report-self-message: false
  # 移除服务端的Reply附带的At
  remove-reply-at: false
  # 为Reply附加更多信息
  extra-reply-data: false

output:
  # 日志等级 trace,debug,info,warn,error
  log-level: warn
  # 是否启用 DEBUG
  debug: false # 开启调试模式

# 默认中间件锚点
default-middlewares: &default
  # 访问密钥, 强烈推荐在公网的服务器设置
  access-token: ''
  # 事件过滤器文件目录
  filter: ''
  # API限速设置
  # 该设置为全局生效
  # 原 cqhttp 虽然启用了 rate_limit 后缀, 但是基本没插件适配
  # 目前该限速设置为令牌桶算法, 请参考:
  # https://baike.baidu.com/item/%E4%BB%A4%E7%89%8C%E6%A1%B6%E7%AE%97%E6%B3%95/6597000?fr=aladdin
  rate-limit:
    enabled: false # 是否启用限速
    frequency: 1  # 令牌回复频率, 单位秒
    bucket: 1     # 令牌桶大小

database: # 数据库相关设置
  leveldb:
    # 是否启用内置leveldb数据库
    # 启用将会增加10-20MB的内存占用和一定的磁盘空间
    # 关闭将无法使用 撤回 回复 get_msg 等上下文相关功能
    enable: true

# 连接服务列表
servers:
  # 添加方式，同一连接方式可添加多个，具体配置说明请查看文档
  #- http: # http 通信
  #- ws:   # 正向 Websocket
  #- ws-reverse: # 反向 Websocket
  #- pprof: #性能分析服务器
  # Zhamao Framework 所需要的服务器配置
  - ws-reverse:
    # 是否禁用当前反向WS服务
    disabled: false
    # 反向WS Universal 地址
    # 注意 设置了此项地址后下面两项将会被忽略
    universal: ws://127.0.0.1:20001/
    # 反向WS API 地址
    api: ws://your_websocket_api.server
    # 反向WS Event 地址
    event: ws://your_websocket_event.server
    # 重连间隔 单位毫秒
    reconnect-interval: 3000
    middlewares:
      <<: *default # 引用默认中间件
```

其中 ws://127.0.0.1:20001/ 中的 127.0.0.1 和 20001 应分别对应炸毛框架配置的 HOST 和 PORT

## 第一次对话

一旦新的配置文件正确生效之后，所在的控制台（如果正在运行的话）应该会输出类似下面的内容：

```verilog
[15:26:34] [I] [#2] 机器人 你的QQ号 已连接！
```

表明机器人已成功连接到炸毛框架了！

这时，如果你是根据安装教程走下来并且未编写任何模块，炸毛自带一个示例模块，里面含有命令：`你好`，`随机数`。如果你对机器人回复：`你好`，它会回复你 `你好啊，我是由炸毛框架构建的机器人！`。这一历史性的对话标志着你已经成功地运行了炸毛框架，开始了编写更强大的 QQ 机器人的创意之旅！

## 编写一个命令

让我们转到框架的模块源代码部分，目录是 `src/Module/Example`，文件是 `Hello.php`。我们插入一段这样的代码：

```php
/**
 * @CQCommand("echo")
 */
public function repeat() {
  $repeat = ctx()->getFullArg("请输入你要回复的内容");
  ctx()->reply($repeat);
  //return $repeat; // 这样的效果等同于 ctx()->reply()
}
```

这样，一个简易的复读机就做好了！回到 QQ 机器人聊天，向机器人发送 `echo 你好啊`，它会回复你 `你好啊`。

<chat-box :my-chats="[
    {type:0,content:'echo 你好啊'},
    {type:1,content:'你好啊'},
    {type:0,content:'echo'},
    {type:1,content:'请输入你要回复的内容'},
    {type:0,content:'哦豁'},
    {type:1,content:'哦豁'},
]"></chat-box>

> 如果你只回复 `echo` 的话，它会先和你进入一个会话状态，并问你 `请输入你要回复的内容`，这时你再次说一些内容例如 `哦豁`，会回复你 `哦豁`。效果和直接输入 `echo 哦豁` 是一致的，这是炸毛框架内的一个封装好的命令参数对话询问功能。有关参数询问功能，请看后面的进阶模块。

## 使用机器人 API 和事件

如果你想不只是回复消息，还要做其他复杂的动作（Action），使用 OneBot Action（又名 OneBot API）进行发送即可，见 [机器人 API](/component/bot/robot-api)。

如果想处理其他类型的事件，比如 QQ 群通知事件等，见 [机器人注解事件](/event/robot-annotations/)。
