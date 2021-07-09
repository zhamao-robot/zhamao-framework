# 模块打包

从 2.5 版本起，炸毛框架的模块源码支持了打包和分发，开发者可以通过将自己的功能编写打包，并通过互联网进行分发，供其他人使用。此外，还提供了模块包热加载（不解包直接运行）和模块包解包功能。

## 构建模块包配置文件

炸毛框架的模块区分是根据 `src` 目录下的文件夹定义的，模块包的配置文件命名必须为 `zm.json`，此外，假设我们编写了一个最简单的模块，以脚手架生成的 Example 模块为例，文件夹结构如下：

```
src/
└── Module/
    ├── Example/
    │   ├── Hello.php
    │   └── zm.json
    └── Middleware/
        └── TimerMiddleware.php

```

我们在 Example 目录下创建一个 `zm.json` 的文件，编写配置，即代表 `src/Module/Example/` 文件夹及里面的用户模块源码为一个模块包，也就可以被框架识别并打包处理。

编写的配置文件结构如下：

```
{
	"name": "my-first-module"
}
```

对！你没看错，只需要定义一个 `name` 字段，即可声明这是一个模块包！

### 配置文件参数

#### - description

- 类型：`string`。

- 含义：模块的描述。

??? note "点我查看编写实例："

	```json
	{
	    "name": "my-first-module",
	    "description": "这个是一个示例模块打包教程"
	}
	```

#### - version

- 类型：string。
- 含义：模块的版本。

版本处理方式和 Composer 基本一致，建议使用三段式，也就是 `大版本.小版本.补丁版本`。关于三段式版本的描述和规范，见 [到底三段式版本号是什么？](https://www.chrisyue.com/what-the-hell-are-semver-and-the-difference-between-composer-version-control-sign-tilde-and-caret.html)。

??? note "点我查看编写实例："
	```json
	{
	    "name": "my-first-module",
	    "description": "这个是一个示例模块打包教程"
	}
	```

#### - depends

- 类型：map of string（例如 `{"foo":"bar","baz":"zoo"}`）。
- 含义：模块的依赖关系和版本依赖声明。

此处用作模块的依赖检测，假设模块 `foo` 依赖模块 `bar` 的 1.x 版本但是不兼容 `bar` 的 2.x 版本，可以像 Composer 的 `require` 一样编写版本依赖：`^1.0`。也可以使用 `~`、`>=`、`*` 这些与 Composer 包管理相同逻辑的版本依赖关系，详见 [Composer - 包版本](https://docs.phpcomposer.com/01-basic-usage.html#Package-Versions)。

??? note "点我查看编写实例："
	```json
	{
	    "name": "foo",
	    "description": "这个是一个示例模块打包教程",
	    "depends": {
	        "bar": "^1.0",
	        "bsr": "*"
	    }
	}
	```

#### - light-cache-store

- 类型：array of string（例如 `["foo","bar"]`）。
- 含义：打包模块时要储存的持久化 LightCache 键名列表。

这里需要配合 LightCache 使用，如果你有一些需要全局缓存的数据，例如动态配置项，比如群服务状态列表，可以先使用 LightCache 存储并使用 `addPersistence()` 持久化，此后在使用模块打包时编写此配置项。

我们假设在项目模块中使用到了 `group-status` 这一个 LightCache，那么只需要写 `light-cache-store` 配置项，在模块打包时就会将持久化的数据也打包到 phar 模块包内。

??? note "点我查看编写实例："
	```json
	{
	    "name": "foo",
	    "description": "这个是一个示例模块打包教程",
	    "light-cache-store": [
	        "group-status"
	    ]
	}
	```

#### - global-config-override

- 类型：string | false。
- 含义：解包时是否需要手动编辑全局配置（`global.php`）。

这里如果写 string 类型的，那么就是相当于在解包时会提示此处的内容，内容推荐填写要求解包模块用户需要编辑的项目，比如 「请将 static_file_server 的 status 改为 true，以便使用静态文本功能」。

如果是 false，那么和不指定此参数效果是一样的，无需用户修改 global.php。

??? note "点我查看编写实例："
	```json
	{
	    "name": "foo",
	    "description": "这个是一个示例模块打包教程",
	    "global-config-override": "请将 static_file_server 的 status 改为 true"
	}
	```

#### - allow-hotload

- 类型：bool。
- 含义：是否允许用户无需解压直接加载模块包文件（phar）。

当此项为 true 时，可以将模块包直接放入 `zm_data/modules` 文件夹下，然后将 `global.php` 中的 `module_loader` 项中的 `enable_hotload` 改为 true，启动框架即可加载。

??? note "点我查看编写实例："
	```json
	{
	    "name": "foo",
	    "description": "这个是一个示例模块打包教程",
	    "allow-hotload": true
	}
	```

!!! warning "注意"
	如果使用允许热加载，那么模块包中的配置最好不要有 `global-config-override` 和 `light-cache-store`，以此来达到最正确的效果，一般热加载更适合 Library（库）类型的模块。

#### - zm-data-store

- 类型：array of string（例如 `["foo","bar"]`）。
- 含义：打包时要添加到模块包内的 `zm_data` 目录下的子目录或文件。

其中项目必须是相对路径，不能是绝对路径，且必须是在配置项 `zm_data` 指定的目录（默认会在框架项目的根目录下的 `zm_data/` 目录。

我们假设要打包一个 `{zm_data 目录}/config/` 目录及其目录下的文件，和一个 `main.png` 文件，下方是实例。

??? note "点我查看编写实例："
	```json
	{
	    "name": "foo",
	    "description": "这个是一个示例模块打包教程",
	    "zm-data-store": [
	        "config/",
	        "main.png"
	    ]
	}
	```

在打包时框架会自动添加这些文件到 phar 插件包内，到解包时，会自动将这些文件释放到对应框架的 `zm_data` 目录下。