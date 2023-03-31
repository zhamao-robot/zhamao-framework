# 框架高级启动

## 框架安装方式

框架提供了多种安装和运行的方式。

- Composer 库引入模式
- 源码模式
- Phar Composer 库引入模式
- Phar 源码模式
- 单文件模式

### Composer 库引入模式

框架在最初的指南教程中，给出的安装方式是 Composer 库引入模式，这种模式安装框架也是比较灵活的。
Composer 库引入模式，顾名思义就是把框架本体当作一个 Composer 库来引入，这样就可以通过 Composer 来管理框架的版本，例如使用 `composer update` 更新。

Composer 库引入模式的文件夹结构如下，我们假设你的项目目录为 `/root/zhamao-v3`：

```
/root/zhamao-v3/
├── composer.json           // 引入框架和项目的 composer.json 描述文件
├── composer.lock           // Composer 生成的 lock 文件
├── config/                 // 炸毛框架依赖的全局配置文件
│   ├── global.php          // 全局配置文件，大部分配置项在这里配置
│   ├── container.php       // 框架的容器配置文件
│   └── motd.txt            // 框架启动时的 MOTD 提示语
├── plugins/                // 开发者编写和安装的插件目录
│   ├── example/            // 插件示例，这里以名称为 example 的插件为例
│   │   ├── main.php        // 这个插件使用了单文件格式，main.php 为插件的核心代码
│   │   └── composer.json   // 这个文件描述了炸毛框架插件的元信息，包括插件名称、版本、作者等
├── vendor/                 // 你自身项目的依赖库，由 Composer 生成
│   ├── zhamao/             
│   │   └── framework/      // 框架的本体源码根目录
│   │       ├── src/ZM/     // 框架的核心代码
│   │       └── ......      // 框架的根目录其他文件
│   └── .......             // 其他依赖库
├── zhamao                  // 启动炸毛框架的入口文件
└── zm_data/                // 存放框架依赖的持久化数据目录，如日志、缓存等
```

### 源码模式

源码模式不是指开发者的源码，而是框架的源码。也就是说，源码模式是从 GitHub 直接拉取框架源码仓库后使用框架的模式。
源码模式允许你对框架本身进行一系列修改，框架本体就可以直接运行。例如，你可以在框架的源码中添加一些自己的代码，或者修改框架的某些功能。

源码模式的结构和 Composer 库引入模式有些许不同，因为框架本身就是一个项目，所以它的目录结构也是一个项目的目录结构。

```
/root/zhamao-framework/
├── composer.json           // 框架本身的 composer.json
├── composer.lock           // Composer 生成的 lock 文件
├── config/                 // 炸毛框架依赖的全局配置文件
│   ├── global.php          // 全局配置文件，大部分配置项在这里配置
│   ├── container.php       // 框架的容器配置文件
│   └── motd.txt            // 框架启动时的 MOTD 提示语
├── plugins/                // 开发者编写和安装的插件目录
│   ├── example/            // 插件示例，这里以名称为 example 的插件为例
│   │   ├── main.php        // 这个插件使用了单文件格式，main.php 为插件的核心代码
│   │   └── composer.json   // 这个文件描述了炸毛框架插件的元信息，包括插件名称、版本、作者等
├── src/                    // 框架的本体源码根目录
│   ├── ZM/                 // 框架的核心代码
│   └── ......              // 框架的根目录其他文件
├── vendor/                 // 框架本身依赖的 Composer 库文件夹
│   └── .......             // 其他依赖库
├── zhamao                  // 启动炸毛框架的入口文件
└── zm_data/                // 存放框架依赖的持久化数据目录，如日志、缓存等
```

源码模式下你可以在 `src/` 目录编写你的项目或修改框架源码运行，此时在 `src/` 下的代码虽然在设置 psr-4 自动加载后会被框架解析，但在该目录下的代码不属于插件的范畴。
如果你不喜欢在插件的形式下开发自己的功能，也可以直接在 src 目录下编写代码。这种方式除了源码模式外，Composer 库引入模式下也可以在你的目录新建一个 `src/` 文件夹并设置自动加载，
以实现在非插件环境下加载你的项目。

### Phar 模式

Phar 模式的意思是将框架和依赖的相关 Composer 库打包为一个可直接使用的 Phar 文件，框架必需的依赖（除 PECL 扩展外）均被包含在一个文件内，方便框架本体分发。

Phar 模式主要面向发布到生产环境和减少小文件，但使用 Phar 模式不便于依赖的更新，所有依赖的库将锁定在打包时的版本。

Phar 模式也分两个小种类，Composer 库引入模式和源码模式。如果你不关注框架本体目录，仅开发功能，无论使用插件形式还是 `src/` 形式，那么在使用上这两种方式没有区别。

框架在未来发布版本时，会同时发布一个打包好的 Phar 版本，你可以直接下载使用。

在使用 Phar 时，你的目录结构可能为这样：

```
/root/your-zhamao-app/
├── plugins/                // 开发者编写和安装的插件目录
│   ├── example/            // 插件示例，这里以名称为 example 的插件为例
│   │   ├── main.php        // 这个插件使用了单文件格式，main.php 为插件的核心代码
│   │   └── composer.json   // 这个文件描述了炸毛框架插件的元信息，包括插件名称、版本、作者等
├── config/                 // 配置文件目录
├── zhamao.phar             // 炸毛框架本体的 Phar
├── zm_data/                // 存放框架依赖的持久化数据目录，如日志、缓存等
```

### 单文件模式

单文件模式和 Phar 模式几乎一样，单文件模式为一个单独的二进制文件，这个二进制文件使用 phpmicro 项目的打包功能将 php-cli 和炸毛框架的 Phar 合成为一个文件，
即可直接使用。这种方式的好处是不需要额外的 php-cli 环境，但是文件体积会比 Phar 模式大一些。

## 框架启动参数

框架启动时可以传入一些参数，这些命令行参数是用于框架启动时的配置。

> 这里框架启动参数指的是 `./zhamao server` 启动框架的参数，而不是 `./zhamao` 命令的参数。有关 `./zhamao` 命令的其他功能，可以参考 [组件 - 命令行](/components/common/cli.html)。

### --config-dir

指定配置文件目录，如果不指定，框架会使用默认的配置文件目录。

```bash
./zhamao server --config-dir=/path/to/your/config/dir
```

### --driver

指定框架使用的驱动，目前支持 `swoole` 和 `workerman` 两种驱动，如果不指定，框架会采用 `config/global.php` 配置文件内的驱动。

```bash
./zhamao server --driver=swoole
```

### --log-level

指定框架组件 zhamao/logger 显示日志的等级。logger 组件符合 psr-3 日志接口，支持设置 8 个等级：

`emergency`、`alert`、`critical`、`error`、`warning`、`notice`、`info`、`debug`。

::: warning 注意

如果你想采用其他 psr-3 日志组件，此配置无效。

:::

```bash
./zhamao server --log-level=debug
```

### --daemon

以守护进程模式启动框架。此参数将直接在输出 motd 后将进程挂到 init 下运行，后台常驻。

> 单进程模式下，此参数无效。

```bash
./zhamao server --daemon  # 执行后，你可以退出当前终端而不退出框架
```

### --worker-num

指定框架启动的 worker 进程数。未指定时默认采用 `config/global.php` 下对应驱动的配置（默认为 1）。

```bash
./zhamao server --worker-num=8
```

::: warning 注意

- 在启动多 worker 时，需要注意无法使用 LightCache，必须切换为 KVRedis 等支持跨进程的组件。
- Windows 环境不支持设置进程数。

:::

### --watch

启动框架的热更新，并启用调试模式。

> 此功能暂未完成，敬请期待。

### --env

设置环境类型 (production, development, staging)。

如果不设置此参数，框架默认使用 development 环境类型。

```bash
./zhamao server --env=production
```

### --disable-safe-exit

禁用安全退出。如果不设置此参数，框架会在收到 SIGINT 信号时，等待所有请求处理完毕后再退出。
设置此参数后，使用键盘 Ctrl+C 会立刻停止所有进程，不会执行退出框架的正常流程，例如保存 LightCache 持久化数据等。

```bash
./zhamao server --disable-safe-exit
```

### --no-state-check

取消框架在启动前的重复启动检查。如果不设置此参数，框架会在启动前检查是否有其他进程正在运行，如果有则会退出。
设置此参数后，框架会忽略重复启动检查，可能会导致多个框架进程同时运行。

```bash
./zhamao server --no-state-check
```

### --private-mode

启动时隐藏框架的配置信息和 MOTD，避免打印到终端。配合 logger 组件的选项可以达到启动时除紧急日志外没有任何输出内容到终端。

```bash
./zhamao server --private-mode
./zhamao server --private-mode --log-level=emergency
```
