# FileSystem 文件系统

文件系统是框架内提供的一个简易的文件管理类。为了让使用框架的开发者更贴近原生的体验，减轻学习负担，这里的 FileSystem 仅提供一些增强的功能。

> 全命名空间：`ZM\Store\FileSystem`。

## scanDirFiles() - 扫描目录

递归或非递归扫描目录，可返回相对目录的文件列表或绝对目录的文件列表。（非常好用）

- 定义：`scanDirFiles(string $dir, bool $recursive = true, $relative = false, bool $include_dir = false)`
- 返回：`bool|array`

参数说明：

- `$dir`：要扫描的目录，必须是绝对路径或 Phar 路径，且路径可读。
- `$recursive`：是否递归扫描子目录，默认为 True，如果设置为 False，则只返回当前目录下的目录和文件列表。
- `$relative`：是否返回相对路径结果。如果为 True，则返回的文件列表为所有文件相对于 `$dir` 目录的相对路径。
- `$include_dir`：如果 `$recursive` 为 False，本项为 True，即非递归模式下，是否包含目录。

当目录无法扫描时，返回 False，并将错误信息由 Logger 发出。

我们假设扫描以下目录，该目录位置为 `/home/ab/test/`，内容有

```
./
└── test-app/
    ├── main.php
    ├── empty/
    └── composer.json
```

```php
$result = \ZM\Store\FileSystem::scanDirFiles('/home/ab/test/', true, false);
/*
结果：
[
  "/home/ab/test/test-app/main.php",
  "/home/ab/test/test-app/composer.json"
]
 */
$result2 = \ZM\Store\FileSystem::scanDirFiles('/home/ab/test/', false, true, true);
/*
结果：
[
  "test-app"
]
*/
```

## isRelativePath() - 检查相对路径

检查路径是否为相对路径（根据第一个字符是否为"/"来判断）。

- 定义：`isRelativePath(string $path)`
- 返回：`bool`
- 参数 `$path`：路径

```php
dump(\ZM\Store\FileSystem::isRelativePath('/a/b/c')); // false
dump(\ZM\Store\FileSystem::isRelativePath('aba/bbb/ccc.php')); // true
dump(\ZM\Store\FileSystem::isRelativePath('C:\\Windows\\')); // false
```

## createDir() - 创建目录

这个方法封装了 `mkdir()` 方法，首先检查目录是否存在，如果不存在就递归创建。

路径默认创建的权限为 755，暂无法调整，如果需要调整，请创建后手动 chmod。

如果创建失败，则抛出一个 `\RuntimeException` 异常。

定义：`createDir(string $path)`

```php
\ZM\Store\FileSystem::createDir('/path/to/your/directory');
```

## getClassesPsr4() - PSR-4 方式读取类列表

根据 PSR-4 规则传入一个目录和基础命名空间，通过扫描文件的方式读取该目录下所有符合 PSR-4 规则的类名。

- 定义：`getClassesPsr4(string $dir, string $base_namespace, mixed $rule = null, bool|string $return_path_value = false)`
- 返回：`array`

参数说明：

- `$dir`：要扫描的 PSR-4 目录。
- `$base_namespace`：该 PSR-4 目录级别的命名空间。
- `$rule`：用于自定义识别过滤文件的回调，默认为 null，即自动使用内建规则。关于内建规则，见下方说明。
- `$return_path_value`：是否返回文件路径，返回文件路径的话传入字符串。

内建规则说明：

- 默认的内建规则中，如果扫描到的目标目录存在该文件的同名加 `.ignore` 后缀的文件，则忽略。
- 如果扫描到的文件以 `script_` 或 `global_` 开头，则忽略。
- 如果扫描到的文件在 `composer.json` 中的 `autoload`.`files` 列表中存在，则忽略。

如果 `$return_path_value` 为字符串，则结果返回一个 map 格式的数组，键名是类名，键值是该值和类的所在文件的拼接。

我们假设目录结构为：`src/TestApp` 的命名空间为 `\TestApp`，下面有文件 `Hello.php` 对应类 `TestApp\Hello`：

```php
$result = \ZM\Store\FileSystem::getClassesPsr4('src/TestApp', 'TestApp\\');
// 结果：["TestApp\\Hello"]
```

## 文件读写

框架默认推荐你使用 PHP 原生的读写文件方式，例如 `file_get_contents`，你也可以引入例如 flysystem 等外部组件。
这取决于你个人的喜好，但在使用第三方文件系统库时需要注意是否兼容协程和多进程。

::: tip 提示

无论在使用 `FileSystem` 类上方列举提供的扩充功能，还是 PHP 原生的读写文件方式，在框架内涉及目录的位置尽量使用绝对路径。

框架提供了一系列目录常量，可以配合目录常量来构成绝对路径。有关常量的定义，见 [常量列表](/components/common/global-defines)。

```php
$file = file_get_contents(WORKING_DIR . '/composer.json');
```

:::
