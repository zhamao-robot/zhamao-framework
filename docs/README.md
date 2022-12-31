---
home: true
heroImage: ./logo_trans.png
actions:
  - text: 快速上手
    link: /guide/
    type: primary
    size: large
  - text: 快速上手（v2 旧版）
    link: https://docs-v2.zhamao.me/
    type: primary
    ghost: true
    size: large
features:
- title: 高性能
  details: 基于 PHP 的 Swoole 高性能扩展，利用 WebSocket 进行与 OneBot 协议兼容的聊天机器人软件的通信，还有数据库连接池、内存缓存、多任务进程等特色，大幅增强性能。
- title: 易于开发
  details: 所有功能采用模块化设计，除特殊情况外几乎所有功能都不需要修改框架内任意代码，框架采用灵活的注解进行各类事件绑定，同时支持下断点调试。
- title: 接口直观
  details: 支持命令、普通文本、正则匹配、自然语言处理等多种对话解析方式，利用协程巧妙实现了直观的交互式会话模式，同时支持多种富文本的处理。
footer: |
  Apache-2.0 Licensed &nbsp;|&nbsp; Copyright © 2019-2022 Zhamao Developer Team &nbsp;|&nbsp; <a href="http://beian.miit.gov.cn">沪ICP备2021010446号-1</a>
---

# 快速上手

## 安装框架和环境

此命令可一键以模板安装框架！（仅限 Linux 和 macOS）

```bash
bash <(curl -fsSL https://zhamao.xin/go.sh)
```

## 运行框架

```bash
cd zhamao-app/
./zhamao server
```

## 效果图

![index_demo](/index_demo.png)
