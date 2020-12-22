# 介绍

> 本文档为炸毛框架 v2 版本，如需查看 v1 版本，[点我](https://docs-v1.zhamao.xin/)。

> 如果是从 v1.x 版本升级到 v2.x，[点我看升级指南](/advanced/to-v2/)。

炸毛框架使用 PHP 编写，采用 Swoole 扩展为基础，主要面向 API 服务，聊天机器人（CQHTTP 对接），包含 websocket、http 等监听和请求库，用户代码采用模块化处理，使用注解可以方便地编写各类功能。

框架主要用途为 HTTP 服务器，机器人搭建框架。尤其对于 QQ 机器人消息处理较为方便和全面，提供了众多会话机制和内部调用机制，可以以各种方式设计你自己的模块。

在 HTTP 和 WebSocket 服务器上，PHP 的扩展 Swoole 提供了高性能的支持，使其效率可媲美 nginx 静态网页处理的效率。

此外，QQ 机器人方面此框架基于 OneBot 标准的反向 WebSocket 连接，比传统 HTTP 通信更快，未来也会兼容微信公众号开发者模式。

```php
/**
 * @CQCommand("你好")
 */
public function hello() {
  ctx()->reply("你好，我是炸毛！");
}
/**
 * @RequestMapping("/index")
 */
public function index() {
  return "<h1>hello!</h1>";
}
```



## 开始前

首先，你需要了解你需要知道哪些事情才能开始着手使用框架：

1. Linux 命令行基础
2. php 7.2+ 开发环境
3. HTTP 协议（可选）
4. OneBot 机器人聊天接口标准（可选）

需要值得注意的是，本教程中所涉及的内容均为尽可能翻译为白话的方式进行描述，但对于框架的组件或事件等需要单独拆分说明文档的部分则需要足够详细，所以本教程提供一个快速上手的教程，并且会将最典型的安装方式写到快速教程篇。

!!! bug "文档提示"

    此文档采用 MkDocs 驱动，但因为本文档的搜索组件原生不支持中文搜索，所以搜索体验会大打折扣，敬请谅解！搜不到不是没这个东西哦！


## 框架特色
- 支持MySQL数据库（连接池），自带查询缓存提高多查询时的效率
- Websocket 服务器、HTTP 服务器兼容运行，一个框架多个用处
- 支持命令、自然语言处理等多种插件形式
- 支持多个机器人账号负载均衡
- 协程 + TaskWorker 进程重度任务处理机制，保证高效，单个请求响应时间为 0.1 ms 左右
- 模块分离和自由组合，可根据自身需求自己建立模块内的目录结构和代码结构
- 灵活的注释注解注册事件方式，弥补 PHP 语言缺少注解的遗憾

## 文档主题

### 主题
<div class="tx-switch">
  <button data-md-color-scheme="default"><code>默认模式</code></button>
  <button data-md-color-scheme="slate"><code>暗黑模式</code></button>
</div>

<script>
  var buttons = document.querySelectorAll("button[data-md-color-scheme]");
  buttons.forEach(function(button) {
    button.addEventListener("click", function() {
      var attr = this.getAttribute("data-md-color-scheme");
      setCookie("_theme", attr);
      document.body.setAttribute("data-md-color-scheme", attr);
      var name = document.querySelector("#__code_0 code span:nth-child(7)");
      name.textContent = attr;
    })
  })
</script>

### 主色调
<div class="tx-switch">
  <button data-md-color-primary="red"><code>red</code></button>
  <button data-md-color-primary="pink"><code>pink</code></button>
  <button data-md-color-primary="purple"><code>purple</code></button>
  <button data-md-color-primary="deep-purple"><code>deep purple</code></button>
  <button data-md-color-primary="indigo"><code>indigo</code></button>
  <button data-md-color-primary="blue"><code>blue</code></button>
  <button data-md-color-primary="light-blue"><code>light blue</code></button>
  <button data-md-color-primary="cyan"><code>cyan</code></button>
  <button data-md-color-primary="teal"><code>teal</code></button>
  <button data-md-color-primary="green"><code>green</code></button>
  <button data-md-color-primary="light-green"><code>light green</code></button>
  <button data-md-color-primary="lime"><code>lime</code></button>
  <button data-md-color-primary="yellow"><code>yellow</code></button>
  <button data-md-color-primary="amber"><code>amber</code></button>
  <button data-md-color-primary="orange"><code>orange</code></button>
  <button data-md-color-primary="deep-orange"><code>deep orange</code></button>
  <button data-md-color-primary="brown"><code>brown</code></button>
  <button data-md-color-primary="grey"><code>grey</code></button>
  <button data-md-color-primary="blue-grey"><code>blue grey</code></button>
  <button data-md-color-primary="black"><code>black</code></button>
  <button data-md-color-primary="white"><code>white</code></button>
</div>

### 辅色调
<div class="tx-switch"> <button data-md-color-accent="red"><code>red</code></button> <button data-md-color-accent="pink"><code>pink</code></button> <button data-md-color-accent="purple"><code>purple</code></button> <button data-md-color-accent="deep-purple"><code>deep purple</code></button> <button data-md-color-accent="indigo"><code>indigo</code></button> <button data-md-color-accent="blue"><code>blue</code></button> <button data-md-color-accent="light-blue"><code>light blue</code></button> <button data-md-color-accent="cyan"><code>cyan</code></button> <button data-md-color-accent="teal"><code>teal</code></button> <button data-md-color-accent="green"><code>green</code></button> <button data-md-color-accent="light-green"><code>light green</code></button> <button data-md-color-accent="lime"><code>lime</code></button> <button data-md-color-accent="yellow"><code>yellow</code></button> <button data-md-color-accent="amber"><code>amber</code></button> <button data-md-color-accent="orange"><code>orange</code></button> <button data-md-color-accent="deep-orange"><code>deep orange</code></button> </div>

<script>
  var buttons = document.querySelectorAll("button[data-md-color-primary]")
  buttons.forEach(function(button) {
    button.addEventListener("click", function() {
      var attr = this.getAttribute("data-md-color-primary")
      setCookie("_primary_color", attr)
      document.body.setAttribute("data-md-color-primary", attr)
      var name = document.querySelector("#__code_2 code span:nth-child(7)")
      name.textContent = attr.replace("-", " ")
    })
  })
</script>

<script>
  var buttons2 = document.querySelectorAll("button[data-md-color-accent]")
  buttons2.forEach(function(button) {
    button.addEventListener("click", function() {
      var attr = this.getAttribute("data-md-color-accent")
      setCookie("_accent_color", attr)
      document.body.setAttribute("data-md-color-accent", attr)
      var name = document.querySelector("#__code_3 code span:nth-child(7)")
      name.textContent = attr.replace("-", " ")
    })
  })
</script>
