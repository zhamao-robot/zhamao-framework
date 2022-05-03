# 错误码对照表

| 异常码 | 含义                                                         | 解决方案                                                     |
| ------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| E00001 | 炸毛框架未检测到 PHP 安装了 Swoole 扩展                      | 根据文档安装扩展去！                                         |
| E00002 | Swoole 扩展安装的版本过低                                    | 升级 Swoole 版本，最好为最新版。                             |
| E00003 | PHP 版本过低                                                 | 升级 PHP 版本，至少为 7.2。                                  |
| E00004 | Swoole 版本低于 4.6.7 且未安装 pcntl 扩展                    | 安装 pcntl 扩展或升级 Swoole 至少为4.6.7。                   |
| E00005 | 在框架命令行解析过程中出现了致命错误                         | 请根据提示的错误位置进行调试和修复，如果未解决请将问题反馈给作者。 |
| E00006 | 炸毛框架在源码模式启动时未能修改 composer.json 文件          | 检查源码模式下 composer.json 文件是否正常可写可读。          |
| E00007 | 框架在启动时未找到 global.php 全局配置文件                   | 如果是使用 `composer create-project` 或用 git 来克隆 starter 仓库的，需要先使用 `vendor/bin/start init` 指令，再启动服务器。 |
| E00008 | 框架在启动时给用于存储连接数据的共享内存表初始化失败         | 请检查系统内存是否过小，如果一切正常，此问题一般是框架内部导致的问题，请将错误日志反馈给开发者。 |
| E00009 | 使用 `--remote-terminal` 时远程终端处理命令出现异常或致命错误 | 检查自身的远程终端是否正确配置和使用，自定义的 `@TerminalCommand` 注解是否抛出了致命错误。 |
| E00010 | 框架在第一步的启动阶段抛出异常或致命错误，导致框架不能继续运行 | 此错误涵盖的错误内容较多，请根据实际抛出的异常内容进行处理或反馈给开发者。<br />如果你使用了 `@SwooleHandler` 或 `@OnSetup` 注解，那么可以自行检查一下注解绑定的函数有没有出错。 |
| E00011 | 框架在调用 Swoole 服务器启动 `$server->start()` 过程中出现了异常 | 此问题未经测试，暂无解决方案，也没有遇到过，如果有发生，请将错误反馈开发者。 |
| E00012 | 框架在启动时调用脚本解析 `@SwooleHandler` 和 `@OnSetup` 时出现了异常 | 留个坑，下次写，TODO。                                       |
| E00013 | 使用命令行参数动态设置启动的 Worker/TaskWorker 进程数时输入了非法的数字 | 填写合法的数字或不使用此功能。                               |
| E00014 | 炸毛框架的启动命令报错，提示没有找到 PHP 环境                | 使用 `./install-runtime.sh` 命令安装便携的静态 PHP 环境或根据教程和 Linux 发行版安装环境。 |
| E00015 | 启动命令启动框架找不到框架本体的入口文件                     | 请检查 Composer 拉取的框架代码是否完整。                     |
| E00016 | 连接中断后 `@OnCloseEvent` 事件抛出异常                      | 检查 `@OnCloseEvent` 或 `@OnSwooleEvent("close")` 注解事件。 |
| E00017 | 框架作为 WebSocket 服务器收到客户端数据后 `@OnMessageEvent`、`@OnSwooleEvent("message")` 或 OneBot 相关事件抛出了未被捕获的异常或错误 | 检查 `@OnSwooleEvent("message")`、`@OnMessageEvent` 或 OneBot 相关注解事件。 |
| E00018 | 框架设置 `access_token` 参数为自定义闭包函数，有新 WebSocket 连接接入但是闭包函数返回失败 | 说白了就是自定义的 `access_token` 验证失败。如果是自己的 OneBot 客户端连接，那么请检查你的函数或 OneBot 客户端那边和框架约定的 token 是否一致，如果将框架开到了公网并有人尝试连接但失败了说明是正常现象。 |
| E00019 | 框架设置了 `access_token` 为固定字符串，但是 WebSocket 新连接验证 Token 失败 | 如果是自身行为，比如 OneBot 客户端接入，请检查 Token 是否一致。如果不需要设置 Token，请检查全局配置文件的 `access_token` 项是否为空字符串。 |
| E00020 | 框架在收到 WebSocket 连接后触发 `@OnOpenEvent` 注解事件过程中抛出了异常 | 检查用户代码中 `@OnOpenEvent`、`@OnSwooleEvent("open")` 注解事件下的代码是否有问题。 |
| E00021 | 框架在处理 pipeMessage 事件时出现了异常                      | 如果写了 `@OnPipeMessageEvent` 注解事件，请检查对应注解事件。如果未设置，可能是框架内部错误，请将报错信息反馈开发者。 |
| E00022 | 调用 `ProcessManager::sendActionToWorker()` 方法时，调用此方法的进程不是 Worker 或 Manager 进程 | 如果你在 Master 进程调用此方法会直接报此错误，框架不支持从 Master 进程调用此方法。 |
| E00023 | 框架在收到 HTTP 请求后处理过程中出现了未捕获的异常           | 检查 HTTP 请求相关的注解解析代码，如果调用栈显示非用户代码所致，请将错误信息反馈开发者。 |
| E00024 | 框架使用 `--watch` 时无法使用热更新并报错                    | PHP 未安装 inotify 扩展，请使用 pecl 安装 inotify 扩展并启用后再试。 |
| E00025 | 框架使用终端输入时产生了未捕获的异常或致命错误               | 检查 `@TerminalCommand` 注解事件或检查使用动态命令的内容（例如 bc 或 call 运行的代码或函数有没有错误）。 |
| E00026 | 框架使用 `@OnTask` 注解在 TaskWorker 进程中执行函数抛出了异常 | 检查 TaskWorker 运行的任务代码是否会抛出未捕获的异常。       |
| E00027 | 框架在运行过程中 Worker 进程发生未捕获的异常导致崩溃退出     | 见 [Issue #38](https://github.com/zhamao-robot/zhamao-framework/issues/38)。 |
| E00028 | PHP 未安装 pdo_mysql（mysqlnd+PDO）扩展                      | 安装 php-mysql（以 ubuntu 为例，apt install php-pdo php-mysql）。 |
| E00029 | PHP 未安装 redis 扩展                                        | 安装 redis 扩展。                                            |
| E00030 | 框架在 Worker 进程启动时出现错误                             | 检查 `@OnStart` 相关事件的问题，或根据报错信息定位问题所在。此问题可能较常见，一般在启动时导致的。 |
| E00031 | 框架在启动前解析代码出现错误                                 | 检查模块代码中是否有 PHP 语法错误。                          |
| E00032 | 上下文的 class 没有 implements ContextInterface 接口         | 如果从 global.php 设置了自定义上下文类，那么请检查上下文类有没有根据文档标准来编写接口。 |
| E00033 | Worker 进程运行过程使用 `zm_*` 方法过程中抛出了未被捕获的异常 | 一般是由 `zm_go()` 或 `zm_timer_tik()` 造成的，协程或计时器内抛出了异常未被捕获。建议根据 trace 检查是什么地方抛出的异常。 |
| E00034 | 由带中间件的 `@OnTick` 计时器产生了未被捕获的异常            | 建议检查计时器内的代码抛出异常位置，如果错误处理也是一部分功能，建议使用 `try catch` 自行捕获。 |
| E00035 | CQ 码相关错误                                                | 根据提示检查调用 CQ 码的代码即可。                           |
| E00036 | OneBot WebSocket API 推送失败，可能是 WebSocket 客户端出现了问题 | 建议检查 OneBot 客户端和框架的连接是否正常。                 |
| E00037 | OneBot 机器人端连接未找到，或单例模式连接了多个机器人        | 根据提示信息进行修复，比如机器人 xxx 未连接到框架，就看一下 OneBot 客户端是否启用和配置正常。 |
| E00038 | 图灵机器人 API 调用出错                                      | 根据提示和图灵错误码进行检查。                               |
| E00039 | 使用 build 命令时检测到目标目录不存在                        | 重新指定一个存在的目录即可。                                 |
| E00040 | 使用 build 命令时检测到 PHP 未设置 `phar.readonly=Off`       | 修改 php.ini 将此项设置为 Off。                              |
| E00041 | 使用 init 命令时未检测到 composer.json 文件                  | 检查引用框架的 composer.json 文件位置。                      |
| E00042 | 框架使用 init 命令时启动模式不是 Composer 模式               | 如果你是使用 git 且下载的仓库是 `zhamao-robot/zhamao-framework.git`，那么代表其以源码模式启动，详见[框架启动模式 - 炸毛框架 v2 (zhamao.xin)](https://framework.zhamao.xin/advanced/custom-start/)。 |
| E00043 | MySQL 数据库出错，抛出异常                                   | 根据提示信息检查 MySQL 语句是否正确，数据库是否连接正常等，其他不能解决的问题建议反馈开发者。 |
| E00044 | 打包模块过程中抛出了异常                                     | 根据提示文本进行修复错误的指令和代码即可。                   |
| E00045 | 打包模块过程中无法储存 `light-cache-store` 项指定的缓存数据  | 根据提示进行修复即可。                                       |
| E00046 | Redis 连接池在使用过程中未提前初始化，可能是未设置全局配置文件启用 Redis 连接池 | 检查 global.php 是否设置 Redis 服务器。                      |
| E00047 | Redis 连接池初始化失败                                       | 根据提示报错信息进行修复，先检查 global.php 是否设置 Redis 服务器。 |
| E00048 | LightCache 未初始化                                          | LightCache 会根据 global.php 初始化申请内存，如果申请出错请根据启动时的报错信息调整配置。 |
| E00049 | LightCache 不能接收字符串、数组、int 之外的变量数据          | 检查传入的数据类型。                                         |
| E00050 | 系统内存不足，LightCache 申请内存失败                        | 让 PHP 可使用的内存或系统内存变大，也可以调小全局配置中设置的 LightCache 配置项。 |
| E00051 | LightCache 的 Hash 冲突过多，导致无法动态空间分配内存        | 设置 `hash_conflict_proportion` 大一些（范围 0-1，默认是 0.6）。 |
| E00052 | 在 /src/ 目录下不可以直接标记为模块(zm.json)，因为命名空间不能为根空间 | 将模块标记文件 zm.json 放到子目录下，不能直接放在 src 目录下。 |
| E00053 | 框架检测到了重名模块                                         | 更改模块名称。                                               |
| E00054 | 打包好的模块文件（phar）内检测不到 zm.json 原始模块标记文件存在 | 检查 phar 模块是否完整。                                     |
| E00055 | 打包好的模块文件（phar）不能正常读取模块标记文件（zm.json）  | 检查 phar 模块是否完整。                                     |
| E00056 | 未开启 TaskWorker 进程                                       | 请先修改 global 配置文件启用。                               |
| E00057 | 调用 `DataProvider::saveToJson()` 失败，因为传入了多级目录   | `saveToJson()` 方法的 `$filename` 参数只能最多到第二级子目录，不能有三级，例如 `foo/bar`。 |
| E00058 | 调用 `DataProvider::scanDirFiles()` 失败，因为传入的 `$relative` 错误 | `$relative` 参数只能传入 `string/false` 两种类型。           |
| E00059 | 使用 `MessageUtil::downloadCQImage()` 失败，因为指定下载的目录不存在 | 新建目录，检查目录地址是否是绝对路径，如果手动指定了目录，最好为绝对路径。 |
| E00060 | 使用 `MessageUtil::downloadCQImage()` 失败，因为图片下载失败 | 检查下载图片的链接地址是否能正常的访问。                     |
| E00061 | 使用 `set_coroutine_params()` 失败，因为不能在非协程环境使用此函数 | 检查调用此函数的位置，注意不能在非协程环境（比如 Master 进程）下调用。 |
| E00062 | 注解事件非法或不可回溯                                       | 不能在非注解调用的类中的方法调用 `EventTracer` 方法。        |
| E00063 | 模块检测到依赖版本问题                                       | 检查是否部署或正确配置依赖的模块/插件版本。                  |
| E00064 | 模块系统检测到依赖的模块不存在或未安装部署                   | 检查依赖的模块是否正确存在于源码目录。                       |
| E00065 | 模块系统检测到打包的模块文件中未含有 `light_cache_store.json` 文件 | 可能是打包此模块后打包的文件损坏，请询问原开发者打包一个新的没有损坏的 phar 文件。 |
| E00066 | 模块打包时 `zmdata-store` 指定的文件或目录不存在             | 检查是否存在，检查写的相对路径是否有误（相对路径的初始路径为框架当前的 `zm_data` 配置的目录。 |
| E00067 | 模块解包合并 `composer.json` 时没有找到项目原文件            | 检查项目的工作目录下是否有 `composer.json` 文件存在。        |
| E00068 | 模块解包时无法正常拷贝文件                                   | 检查文件夹是否正常可以创建和写入。                           |
| E00069 | 框架不能启动两个 ConsoleApplication 实例                     | 不要重复使用 `new ConsoleApplication()`。                    |
| E00070 | 框架找不到 `zm_data` 目录                                    | 检查配置中指定的 `zm_data` 目录是否存在。                    |
| E00071 | 框架找不到对应类型的 API 调用类                              | 检查 `getExtendedAPI($name)` 传入的 `$name` 是否正确                    |
| E00072 | 上下文无法找到                                              | 检查上下文环境，如是否处于协程环境中                             |
| E00073 | 在类中找不到方法                                              | 检查调用对象是否存在对应的方法（method）或检查是否插入了对应的macro宏方法     |
| E00074 | 参数非法                                              | 检查调用的参数是否正常（此处可能有多处问题，请看具体调用栈炸掉的地方）     |
| E00075 | Cron表达式非法                                              | 检查 @Cron 中的表达式是否书写格式正确     |
| E00076 | Cron检查间隔非法                                              | 检查 @Cron 中的检查间隔（毫秒）是否在1000-60000之间     |
| E00077 | 输入了非法的消息匹配参数类型                                              | 检查传入 `@CommandArgument` 或 `checkArguments()` 方法有没有传入非法的 `type` 参数     |
| E99999 | 未知错误                                                     |                                                              |
