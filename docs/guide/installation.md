# 安装

我们希望尽可能轻松地使用炸毛，不论是本地开发或是部署都提供多种选择，尽量覆盖所有需求。

- 一键脚本
- Docker
- Composer
- Phar 发行模式

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

## Phar 发行模式

如果你对框架本身不感兴趣，只是想使用框架提供的功能，你可以直接下载 Phar 发行模式的框架，然后在任意目录下使用。

你可以到 GitHub Release 下载框架的自动打包 Phar 版本，同时下载一个静态的 PHP 运行时，然后将二者放在同一目录下，即可使用。
但请注意，测试版一般不会发布 Phar 包，因此你需要自行构建。

如果你的操作系统已经安装好了 PHP 并设置了环境变量，你也可以直接使用 `./zhamao.phar` 来运行框架。

> 如果在 Linux、macOS 环境下提示权限不足，请使用 `chmod +x zhamao.phar` 来授予执行权限。

## Windows 安装方法

由于 Windows 系统下的 PHP 环境配置较为复杂，我们推荐使用 Docker 或一键脚本来进行安装。

如果你打算在 Windows 使用原生的 Win 环境 PHP，你需要先安装 PHP 和 Composer，然后在任意目录下执行上方 composer 的安装方法即可。

### 包管理安装

Windows 也可以使用包管理安装 PHP、Composer，例如你可以使用 Scoop 包管理进行安装：

```powershell
scoop install php
scoop install composer
```

采用这种包管理安装后，可直接使用 `php`、`composer` 命令在任意位置，无需配置环境变量。

如果你使用包管理或已经安装了 PHP 到系统内，接下来就直接使用 Composer 来安装框架即可！

```powershell
composer create-project zhamao/framework-starter zhamao-v3
cd zhamao-v3
./zhamao plugin:make
./zhamao server
```

### 纯手动安装

如果你不想使用包管理的方式安装 PHP，且让 PHP 仅框架独立使用，你可以依次采用以下的方式来安装 PHP、Composer 和框架：

1. 从 GitHub 下载框架的脚手架，地址：<https://github.com/zhamao-robot/zhamao-framework-starter/archive/refs/heads/master.zip>
2. 解压框架脚手架，重命名文件夹名称为你自己喜欢的名称，例如 `zhamao-v3`。
3. 从 PHP 官网下载 PHP，选择 `Non Thread Safe` 版本，PHP 版本选择 8.0 ~ 8.2 均可（推荐 8.1），下载完成后解压到框架目录下的 `runtime\php` 目录，例如 `D:\zhamao-v3\runtime\php\`。
4. 从 [Composer 官网](https://getcomposer.org/download/) 或 [阿里云镜像](https://mirrors.aliyun.com/composer/composer.phar) 下载 Composer，下载到 `runtime\` 目录。
5. 在你的脚手架目录下执行 `.\runtime\php\php.exe .\runtime\composer.phar install` 安装框架依赖。
6. 执行框架初始化命令：`./zhamao init`。（如果提示没有 `./zhamao` 文件，本步骤可尝试使用 `.\runtime\php\php.exe .\vendor\bin\zhamao init` 来执行）
7. 接下来你就可以使用和上方所有框架操作指令相同的内容了，例如 `./zhamao plugin:make`、`./zhamao server` 等。
8. 如果你需要使用 Composer，你可以使用 `.\runtime\php\php.exe .\runtime\composer.phar` 来代替 `composer` 命令。

## 提升性能

如果你打算让你的框架提升处理性能，我们建议你为 PHP 安装 Swoole 扩展、libevent 扩展，或将 PHP 版本提升到 8.1 及以上。

## 更多的环境部署和开发方式

除了上述方式之外，框架还支持源码模式、守护进程等运行方式，详情请参阅 [进阶开发](/advanced/)。
