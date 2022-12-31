# 安装

我们希望尽可能轻松地使用炸毛，不论是本地开发或是部署都提供多种选择，尽量覆盖所有需求。

- 一键脚本
- Docker
- Composer

## 一键脚本

炸毛框架提供了一键脚本来设置运行环境并拉取脚手架，帮助你快速进行开发。

如果检测到本机未安装 PHP 或不符合运行要求，脚本将会自动拉取提前编译好的静态 PHP 运行时。

```shell
# 将静态 PHP 和框架安装在当前目录（适用于 Linux、macOS）
bash <(curl -fsSL https://zhamao.xin/v3.sh)

# 安装完成后启动
./zhamao server
```

> 关于静态运行时的更多用法，请参见 这里是链接

## Docker

待完善

## Composer

如果你已经有了必要的 PHP 环境和 Composer 工具，你可以直接在任意目录下初始化框架。

> 由于目前 3.0 仍处于预发布阶段，请使用 `composer require zhamao/framework:^3` 来安装

```shell
# 在当前目录初始化框架
composer require zhamao/framework
./vendor/bin/zhamao init

## 安装完成后启动
./zhamao server

## 生成新插件脚手架，用于开发
./zhamao plugin:make
```

## Windows 安装方法

由于 Windows 系统下的 PHP 环境配置较为复杂，我们推荐使用 Docker 或一键脚本来进行安装。

如果你打算在 Windows 使用原生的 Win 环境 PHP，你需要先安装 PHP 和 Composer，然后在任意目录下执行上方 composer 的安装方法即可。

## 更多的环境部署和开发方式

除了上述方式之外，框架还支持源码模式、守护进程等运行方式，详情请参阅 [进阶开发]。
