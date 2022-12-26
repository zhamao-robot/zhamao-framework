# 目录结构

## 根目录

### Config 目录

`config` 目录包含框架、应用的所有配置文件。最好把这些文件都浏览一遍，并熟悉所有可用的选项。

### Src 目录

`src` 目录包含应用的核心代码，你的大部分工作都将在这里进行。

### Tests 目录

`tests` 目录通常是你编写 PHPUnit 单元测试和功能测试的地方。你可以使用 `composer test` 运行其中的测试。

> 该目录并不自带
>

### Vendor 目录

`vendor` 目录包含你通过 Composer 安装的所有依赖。

## Src 目录

你的大多数代码都位于 `src` 目录中。

### Globals 目录

`globals` 目录包含你的全局定义文件，例如全局函数和常量等。

需要注意的是，框架本身并不会为你自动加载其中的文件，你需要自行使用 Composer 自动加载或其他方式加载其中的代码。

例如 `Globals/my_functions.php` 可以被添加到 `composer.json` 当中。

```json
{
	"autoload": {
		"files": [
			"src/Globals/my_functions.php"
		]
	}
}
```

### Module 目录

`module` 目录包含你机器人或是服务的主体代码，其中的所有类都会被框架自动扫描并解析，你可以在其中利用注解来注册事件绑定并进行相应处理。
