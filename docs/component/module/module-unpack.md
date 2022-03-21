# 模块解包

从 2.5 版本起，炸毛框架的模块源码支持了打包和分发，分发后必不可少的一步就是将其解包。

解包过程大致为：

1. 检查模块的配置文件是否正常。
2. 检查模块的依赖问题，如果有依赖但未安装，则抛出异常。
3. 检查 LightCache 轻量缓存是否需要写入。
4. 检查 `zm_data` 是否有需要存入的数据。
5. 合并 `composer.json` 文件。
6. 拷贝 `zm_data` 相关的文件。
7. 写入 LightCache 相关数据。
8. 提示用户手动合并 `global.php` 全局配置文件。
9. 拷贝模块 PHP 源文件。

## 解包命令

```bash
./zhamao module:unpack <module-name>
```

首先将待解包的 phar 文件放入 `zm_data` 目录下的 `modules` 文件夹（如果不存在需要手动创建），如果你手动修改过 `global.php` 下面的 `module_loader.load_path` 项，需要放入对应的目录。

放入后，结构如下：

```
zm_data/
zm_data/modules/
zm_data/modules/foo.phar
```

接下来，需要知道模块的名称。当然一般情况下，phar 的名称可以获取到模块的实际名称，如 `foo`，但最好用 `./zhamao module:list` 列出模块的信息来获取真实的模块名称。

```
./zhamao module:list
# 下面是输出
[foo]
        类型: 模块包(phar)
        位置: zm_data/modules/我是假的名字.phar
```

解包过程十分简单，只需要执行一次命令即可。

```
./zhamao module:unpack foo
# 下面是输出
[10:05:40] [I] Releasing source file: src/Module/Example/Hello.php
[10:05:40] [I] Releasing source file: src/Module/Example/zm.json
[10:05:40] [S] 解压完成！
```

### 命令参数

在解包时会遇到各种复杂的情况，如源码文件已存在、数据已存在、依赖问题等，通过增加参数可以控制解包时的行为。

#### --overwrite-light-cache

含义：覆盖现有的 LightCache 键值（如果存在的话）。

#### --overwrite-zm-data

含义：覆盖现有的 `zm_data` 下的文件（如果存在的话）。

#### --overwrite-source

含义：覆盖现有的 PHP 模块源文件（如果存在的话）。

#### --ignore-depends

含义：解包时忽略检查依赖。

### 常见问题

如果你解包的模块包要求修改 `global.php`，则会出现类似这样的提示：

```
# ./zhamao module:unpack foo
[14:47:39] [W] 模块作者要求用户手动修改 global.php 配置文件中的项目：
[14:47:39] [W] *请把全局配置文件的light_cache项目中max_strlen选项调整为至少65536
请输入修改模式，y(使用vim修改)/e(自行使用其他编辑器修改后确认)/N(默认暂不修改)：[y/e/N]
```

一般这种情况，根据第二条提示（第二条提示为打包时填入的 `global-config-override`）。如果输入 y，则会自动执行命令 `vim config/global.php`，如果输入的是 e，则会等待你手动修改完成文件，最后按回车完成修改。默认情况直接回车的话，会跳过此步骤，如果模块要求了修改但跳过修改，安装后可能会有功能缺失等问题。
