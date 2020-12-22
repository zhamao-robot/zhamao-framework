# 编写模块

到现在为止，我们还在使用框架的默认模块 `Example/Hello.php`，在开始编写自己的模块应用之前，我们先说明一些编写代码的约定。

## 加载模块

框架默认使用脚手架构建好后，目录结构大致为下面这样：

```bash
zhamao-framework-starter/
├── config/                 # 项目的配置文件文件夹，如 global.php
├── src/                    # 项目的主要源码目录
│   ├── Module/             # 用户编写的模块目录
│   │   └── Example/        # 模块文件夹名称
│   │       └── Hello.php   # 模块内的类
│   └── Custom/             # 用户自定义的全局方法、全局注解类等存放的目录
├── vendor/                 # Composer 依赖加载目录
└── composer.json           # Composer 配置文件
```

其中我们脚手架包含的默认模块 `Example` 下的 `Hello` 类，就是用户写模块的位置。你也可以根据实际情况，自行添加更多的模块文件夹甚至单文件模块。

需要注意的是，所有文件夹名称和 `.php` 文件必须遵循 [psr-4 规范](https://learnku.com/docs/psr/psr-4-autoloader/1608)，简单来说，`src/` 目录下的文件夹，子文件夹要写成命名空间，比如默认框架中 `Example/` 下的 `.php` 文件的命名空间为 `namespace Module\Example;`，且一个 `.php` 文件推荐只包含一个 `class`、`trait` 或 `interface`。

```php
<?php
namespace Module\<your-module-dir>;
class ModuleA {}
```

!!! fail "警告"
	如果没有遵守上方的类和文件命名规则的话（文件名、文件夹名和命名空间的统一性），在加载框架时就会报错，无法找到对应的类。因为框架的注解解析依赖于 Composer 中 psr-4 规则的自动加载。

## 创建模块
### 标准形式
我们这里以 `Entertain` 娱乐模块的创建为例，新建一个内有 `Dice.php` 掷骰子功能的模块，目录结构如下，在 `Module/` 下新建文件夹 `Entertain/`，再在此子目录下新建 `Dice.php` 文件。
```bash
zhamao-framework-starter/
└── src/
    └── Module/
        └── Entertain/
            └── Dice.php
```
新建的 PHP 文件按照如下方式编写：
```php
<?php
namespace Module\Entertain;
class Dice {
}
```

这个时候它已经可以被称为一个模块了，尽管它还什么都没做。

### 单文件形式

如果你只开发很简单的一些功能，如一个 PHP 文件就可以实现的，可以少去创建模块文件夹的一步，直接将 `.php` 文件新建到 `Module/` 文件夹下，这时此文件的命名空间需要更正为 `namespace Module;` 即可，而文件夹结构也更加简单：

```bash
zhamao-framework-starter/
└── src/
    └── Module/
        └── Dice.php
```

### Composer 外部引入形式

（暂未支持，敬请期待）

