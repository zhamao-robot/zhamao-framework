# 框架高级启动

## 框架下载方式

从前面的几章中，我们了解到框架有多种下载到本地的方式。

- Composer 依赖模式
- Starter 从模板创建模式
- 源码模式

### Composer 依赖模式

从 Composer 依赖加载框架是一种拉取框架的方式，这种方式的优点在于，你可以直观地感受到是如何使用框架从零开始一个完整的项目的过程。

从 Composer 依赖的启动步骤：

```bash
mkdir my-bot # 新建一个空的文件夹
cd my-bot/
composer require zhamao/framework # 从 composer 拉取后会自动部署 autoload 和 composer.json 等内容

# 使用命令初始化框架
vendor/bin/start init

# 启动框架
vendor/bin/start server
```

注意：使用 `init` 命令时，会给当前目录解压以下文件：

```php
$extract_files = [
    "/config/global.php",							// 全局配置文件
    "/.gitignore",									// git 排除文件
    "/config/file_header.json",						// HTTP 文件头
    "/config/console_color.json",					// 终端颜色主题文件
    "/config/motd.txt",								// 框架启动时自定义的 motd
    "/src/Module/Example/Hello.php",				// 框架自带的示例模块
    "/src/Module/Middleware/TimerMiddleware.php",	// 框架自带的函数运行时间监控中间件
    "/src/Custom/global_function.php"				// 用户可在这里自定义编写自己的全局函数
];
```

经过 init 解压这些文件后，你的框架就能正常运行且开始编写代码了！

### Starter 模板模式

从模板新建其实原理和 Composer 依赖模式完全一样，只不过，这个过程是使用模板仓库新建的项目，使用 Composer 自带的 `create-project` 方式创建的。starter 也是一个 GitHub 项目，见 [地址](https://github.com/zhamao-robot/zhamao-framework-starter)。

```bash
composer create-project zhamao/framework-starter my-bot/ # my-bot 是你自定义的文件夹名称，和上方相同
cd my-bot
vendor/bin/start server # 启动框架
```

Starter 模式相当于直接从 GitHub 拉取 `zhamao-framework-starter` 项目，然后执行 `composer update`。

那和 Composer 依赖模式有什么区别呢？没区别！构建出来的框架和文件是一模一样的！使用 Composer 依赖模式，使用 `init` 命令后，文件会和 `zhamao-framework-starter` 仓库拉取回来的模板一模一样！（或者换句话说，这个仓库就是使用 `init` 命令生成的文件的）

那使用哪种好呢？看你自己！如果你想给你自己的已有项目套上炸毛框架，那么就推荐使用 Composer 依赖模式，如果是从 0 开始编写框架模块，则推荐使用模板模式。

### 源码模式

源码模式和以上两种方案都不一样，源码模式允许你对框架本身进行一系列修改，框架本体就可以直接运行。

Composer 依赖模式（以及模板模式）和源码模式的区别是：

- 依赖模式和模板模式是通过 library 方式引入框架的，框架本身会放在 composer 的 `vendor/` 目录下，从 composer 引入的 library 相当于子集，vendor 目录下的文件最好不要手动修改（应该都知道吧），所以框架本身也只是加载了进来。
- 源码模式相当于直接从框架源码目录运行框架和模块，框架源码都在 `src/ZM` 目录下，默认的示例模块都在 `src/Module` 下，是同级目录。而此时的 `vendor/` 目录只包含了框架依赖的外部组件，例如注解解析器和 psysh 等。

源码模式可以方便地调试和修改框架本身，拉取方式很简单，用 `git clone` 或从 GitHub 下载最新版的源码包解压即可。

```bash
git clone https://github.com/zhamao-robot/zhamao-framework.git
cd zhamao-framework/
bin/start server # 第一次运行时会提示一个“框架源码模式需要在autoload文件中添加Module目录为自动加载”
composer update # 更新 autoload 文件，应用刚才上一步添加的 `src/Module` 文件夹下的模块自动加载
bin/start server # 通过源码模式启动框架
```

## 框架启动参数

框架启动时可以根据实际情况指定启动参数。

- `--debug-mode`：启用调试模式，调试模式的作用是关闭一键协程化和终端交互，减少 Swoole 本身对代码逻辑的干扰（比如执行 `shell_exec()` 报错的话可以开启这个进行调试）。
- `--log-{mode}`：设置 log 等级。支持 `--log-debug`，`--log-verbose`，`--log-info`，`--log-warning`，`--log-error`。
- `--log-theme`：设置终端信息的主题。这个选项适用于多种终端信息显示的兼容，例如白色终端和不支持颜色的终端。详见 [Console - 主题设置](/component/console/#_2)。
- `--disable-console-input`：关闭终端交互，如果你使用的不是 tmux、screen 而是直接将进程使用 systemd 等方式运行到 init 守护进程下，则需要关闭终端交互输入，关闭后不可以使用 `stop, reload, logtest` 等交互命令。
- `--disable-coroutine`：关闭一键协程化。
- `--daemon`：以守护进程方式运行框架，此参数将直接在输出 motd 后将进程挂到 init 下运行，后台常驻。
- `--watch`：监控 `src/` 目录下的文件变化，有变化则自动重新载入代码。开启监控需要安装 PHP 扩展：inotify。使用 pecl 就可以安装：`pecl install inotify`。
- `--env`：设置运行环境，设置运行环境后将优先加载指定环境的配置文件，支持 `--env=production`，`--env=staging`，`--env=development`，见 [基本配置](/guide/basic-config/#_2)。

## 独立启动其他组件

框架默认不止启动框架的 `server` 命令，还有 `init` 命令和 `simple-http-server` 命令。`init` 命令在上方 Composer 依赖模式中提到过，就是初始化各个文件的。

### 独立 HTTP 文件服务器

如果你只需要一个静态文件服务器，类似 Nginx，那么框架也支持。

```bash
vendor/bin/start simple-http-server your-web-dir/ --host=0.0.0.0 --port=8080
```

-  `your-web-dir` 是必填的参数。
- `--host` 和 `--port` 是可选参数，如果不填，则默认使用 `global.php` 配置文件中的配置。