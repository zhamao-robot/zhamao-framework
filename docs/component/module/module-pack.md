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



| 字段名                 | 类型                               | 含义                                     |
| ---------------------- | ---------------------------------- | ---------------------------------------- |
| description            | string                             | 模块的描述                               |
| version                | string                             | 模块的版本（建议使用 x.y.z 三段式）      |
| depends                | array of string（例如`{"a":"b"}`） | 模块依赖关系声明                         |
| light-cache-store      | string[]                           | 打包模块时要储存的持久化 LightCache 键名 |
| global-config-override | string \| false                    | 是否需要手动编辑全局配置（`global.php`） |
|                        |                                    |                                          |

