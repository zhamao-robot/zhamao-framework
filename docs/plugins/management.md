# 插件管理

炸毛框架内置了插件管理功能。借助插件管理功能，你可以下载、管理、开发插件。
插件像搭建积木一样快速构建自己的应用，在插件市场内的插件数量足够多的时候，你甚至可以不写任何一行代码来构建自己的应用。
例如，引入机器人群管、机器人问答、对接 GPT 等 API 接口，也可以自己开发一个插件，发布到插件市场或 Composer。

## GitHub 插件下载

你可以到 [插件市场](/plugins/market.md) 下载并安装你心仪的插件，这些插件大部分为 OneBot 机器人适用的聊天机器人功能类插件。

在安装框架后，可以使用 `./zhamao plugin:install https://github.com/xxx/yyy.git` 样式的链接安装 GitHub 发布的插件。

这里以官方插件**一言**为例子：

```bash
./zhamao plugin:install https://github.com/awesome-zhamao/hitokoto.git
```

在短暂等待后，出现 `插件 zhamao-plugin/hitokoto 安装成功！` 的字样，即表示插件 `hitokoto` 安装成功。

使用框架的安装命令安装插件均会使用 Composer 进行管理，例如上方在使用 `plugin:install` 命令传入了 GitHub 仓库地址后，
根据对应外部插件的 `composer.json` 获取插件名称。

如果从 GitHub 下载插件时候提示你的 API 接口被限制速率，例如请求返回了非 200 的状态码，先需要到 GitHub 个人设置页面申请一个 `Classic Token`。
然后使用命令参数 `--github-token=XXXXXXXXXXXXXX`，XXX替换为你的 Token 值，例如：

```bash
./zhamao plugin:install https://github.com/awesome-zhamao/hitokoto.git --github-token=fergv3w4t34tcx3w4tw45hw64
```

## Composer 插件下载

一般情况下，普通小插件可以使用 GitHub 仓库进行发布，如果你对插件的使用者体验比较在意，这里更推荐开发者将插件发布到 packagist.org。
发布到 packagist.org 的插件我们称之为 Composer 插件，与 GitHub 插件不同的是，Composer 插件仅需使用 `xxx/yyy` 名称直接安装。

这里还以官方的一言插件为例，如果使用 Composer 插件安装方式，使用命令：

```bash
./zhamao plugin:install zhamao-plugin/hitokoto
```

这里如果安装的是发布到 Composer 的插件，效果与 `composer require zhamao-plugin/hitokoto` 是一样的。

## 插件卸载

如果你想移除某个从 Git、Composer 安装的炸毛框架插件，可以使用命令 `plugin:remove`（在 3.1.7 版本后可用）：

```bash
./zhamao plugin:remove zhamao-plugin/hitokoto
```

需要注意的是，这里只能用插件名称，不可以使用插件的 Git 仓库链接、插件文件夹等。

## 插件列表展示

使用命令 `plugin:list` 可以查看当前框架安装了哪些插件，包含 Phar 插件、开发的插件、Composer 插件。

```bash
./zhamao plugin:list
```

你也可以使用参数 `--name-list` 来获取一个只有名称的插件列表：

```bash
./zhamao plugin:list --name-list
```
