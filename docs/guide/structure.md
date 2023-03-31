# 目录结构

## 用户目录

### config 目录

`config` 目录包含框架、应用的所有配置文件。最好把这些文件都浏览一遍，并熟悉所有可用的选项。

```
config/
├── global.php           # 全局配置文件
├── container.php        # 容器配置文件
└── motd.txt             # 框架启动时展示的文字信息
```

### vendor 目录

`vendor` 目录包含你通过 Composer 安装的所有依赖，此目录为自动生成，无需操作。

### plugins 目录

`plugins` 目录包含你编写或加载到源代码模式的插件，里面的插件都会被框架自动扫描并解析，你可以在其中利用注解来注册事件绑定并进行相应处理。

比如你通过 `./zhamao plugin:make` 新建了一个名字叫 `test-app` 的插件，并且设置为单文件模式（`file`），那么这个插件内包含的文件及结构为：

```
plugins/
└── test-app/
    ├── main.php         # 你的插件源代码文件
    └── composer.json    # 插件元信息（如名称、版本等）
```

### zm_data 目录

`zm_data` 目录存放了框架运行时持久化保存的数据，例如 KV 数据库、驱动日志等内容。

