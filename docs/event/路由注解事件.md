# 路由注解事件

炸毛框架提供了一个简易但是高效易用的 HTTP 路由注解，你可以使用路由功能来开发任何 Web 应用微服务、API 接口、中间件等。

!!! quote "开发提示"

	本章节涉及的路由和控制器概念可能和其他传统框架有一些出入，而且炸毛框架非绝对根据 PSR 标准进行开发，目的是使用上一些常见的东西尽可能地灵活和不罗嗦。

## 控制器和路由

Controller 和 Route 为路由注解事件的核心注解事件，其中 Controller 的注解事件为 `@Controller`，Route 的注解事件为 `@RequestMapping`。

### Controller()

#### 属性

| 类型       | 值                              |
| ---------- | ------------------------------- |
| 名称       | `@Controller`                   |
| 触发前提   | 当路由 url 匹配到时进入触发     |
| 命名空间   | `ZM\Annotation\Http\Controller` |
| 适用位置   | 类                              |
| 返回值处理 | 对类注解修饰，无返回值          |

#### 参数

| 参数名称 | 参数范围       | 用途         | 默认 |
| -------- | -------------- | ------------ | ---- |
| prefix   | `string`，必需 | 控制器的 url | 空   |

### RequestMapping()

#### 属性

| 类型       | 值                                                          |
| ---------- | ----------------------------------------------------------- |
| 名称       | `@RequestMapping`                                           |
| 触发前提   | 当路由 url 匹配到时进入触发                                 |
| 命名空间   | `ZM\Annotation\Http\RequestMapping`                         |
| 适用位置   | 方法                                                        |
| 返回值处理 | 返回类型是 `string` 时，自动调用 HTTP 响应并返回 200 状态码 |

#### 参数

| 参数名称       | 参数范围                                                    | 用途                       | 默认                                       |
| -------------- | ----------------------------------------------------------- | -------------------------- | ------------------------------------------ |
| route          | `string`，必需                                              | 控制器的 url               | 空                                         |
| name           | `string`                                                    | 路由的名称                 | 空                                         |
| request_method | `array`，限定 `RequestMethod::GET` 等常量                   | 限制激活路由的 HTTP 方法   | `[RequestMethod::GET,RequestMethod::POST]` |
| params         | `array`，当路由中含有如 `{id}` 类似的动态路由时，会动态改变 | 动态参数的路由参数值的绑定 | `[]`                                       |

#### 函数调用参数

- `$param`：如果路由中存在变量（动态路由），则会把动态路由所匹配的参数放入 `$param` 数组中。

```php
/**
 * @RequestMapping(route="/test/{ass}")
 */
public function testName($param) {
    return "Your name is ".($param["ass"] ?? "unknown");
} 
```



### 路由示例

=== "代码"
	```php
	<?php
	namespace Module\Example;
	
	use ZM\Annotation\Http\Controller;
	use ZM\Annotation\Http\RequestMapping;
	/**
	 * @Controller("/api")
	 */
	class Hello {
	    /**
	     * @RequestMapping("/index")
	     */
	    public function index(){
	        ctx()->getResponse()->end("This is API index page"); // 使用上下文获取响应对象
	    }
	  	/**
	  	 * @RequestMapping("/ping")
	  	 */
	    public function ping(){
	        return "pong"; // 直接返回字符串
	    }
	}
	```

=== "效果"

	!!! example "效果描述"
		当访问浏览器的 `http://localhost:20001/api/index` 时，浏览器会返回 `This is API index page`，当访问 `/api/ping` 的 url 时，浏览器会返回 `pong`。
		
		```
		/            -> 无任何路由
		/api/index   -> Hello->index
		/api/ping    -> Hello->ping
		```

!!! tip "提示"

	当 `@Controller` 为 `/` 的时候，效果和不写是一样的，`@RequestMapping` 为 `/` 或 `/index/inside` 等多级路由也是可以的。

### 绑定参数

在 `@RequestMapping` 中，不仅可以写静态的路由地址，也可以写绑定的参数。例如：`@RequestMapping(route="/index/{name}")`，则访问 `/index/xxx` 的时候，你在函数方法内可以这样获取此参数：

```php
/**
 * @RequestMapping("/index/{name}")
 */
public function index($arg) {
    return "Your param 'name' is ".$arg["name"];
}
```

## 获取请求参数 GET / POST

炸毛框架支持获取外部 HTTP 请求进来的 GET 和 POST 请求，通过获取 HTTP 请求对象 [Request](/advanced/inside-class/) 即可。对象具体属性和方法点这个链接进去就行。

### 示例

=== "获取 GET"

	```php
	/**
	 * @RequestMapping("/testUrl")
	 */
	public function testUrl() {
	  $get = ctx()->getRequest()->get;
	  if(isset($get["name"])) return "hello, ".$get["name"];
	  else return "Unknown name!!";
	}
	```

=== "获取 POST（x-www-form-urlencoded）"

	```php
	/**
	 * @RequestMapping("/testUrl")
	 */
	public function testUrl() {
	  $post = ctx()->getRequest()->post;
	  if(isset($post["name"])) return "hello, ".$post["name"];
	  else return "Unknown name!!";
	}
	```

=== "获取 JSON POST"

	```php
	/**
	 * @RequestMapping("/testUrl")
	 */
	public function testUrl() {
	  $post = ctx()->getRequest()->rawContent();
	  $json = json_decode($post, true);
	  if ($json === null) return "Invalid json data!";
	  if(isset($json["name"])) return "hello, ".$json["name"];
	  else return "Unknown name!!";
	}
	```

## 设置路由请求方式

如果想要设置允许请求控制器的 HTTP 请求方式，可以使用方法在控制器中的 `@RequestMapping` 注解配置 `method` 参数，可以是 `GET`，`POST`，`PUT`, `PATCH`，`DELETE`，`OPTIONS`，`HEAD` 中的一个或多个。

- 限定 HTTP 方法：`@RequestMapping(method="GET")`，`@RequestMapping(method={"GET","POST"})`

## 静态文件服务器

框架支持了静态文件的访问。如需使用，则需要先到配置文件中配置相应的 `static_file_server` 参数中 `status` 为 `true`。

框架分为两种静态文件服务器，一种是全局的静态文件服务器，比如框架部署在 `http://127.0.0.1:20001/` 上通过 HTTP 访问，如果没有访问到 `@RequestMapping` 注解事件注册的路由地址，则会通过 url 自动查找静态文件服务器设置的根路径下面的文件，如果都不存在则会返回 404。

### 配置全局静态文件服务器

我们假设在你写的框架应用的根目录下，有如下文件和内容：

```
resources/html/hello.html (下面是内容)
<html>
<head>
<meta charset="utf-8">
</head>
<body>
框架文档内容太多了，写不完！！！
</body>
</html>
```

然后在 `global.php` 配置文件中静态文件服务器参数为：

```php
/** 静态文件访问 */
$config['static_file_server'] = [
    'status' => true,
    'document_root' => realpath(__DIR__ . "/../") . '/resources/html',
    'document_index' => [
        'index.html'
    ]
];
```

最终，我们通过 `vendor/bin/start server` 等方式，启动框架后，浏览器访问 `http://127.0.0.1:20001/hello.html` 即可获取内容。

### 配置局部静态文件服务器

所涉及的类的命名空间：`use ZM\Http\StaticFileHandler;`

局部静态文件服务器一般用于，比如机器人要发送图片，或者给其他 HTTP 服务提供文件下载的接口时可用。我们假设写了一个图片收集的一个静态文件夹区域，将其中一个子路由当作图片静态目录：

```php
/**
 * @RequestMapping("/images/{filename}")
 * @param $param
 * @return StaticFileHandler
 */
public function staticImage($param) {
  Console::info("[下载图片] " . $param["filename"]);
  return new StaticFileHandler($param["filename"], "/path/to/your/image_dir/");
}
```

这样当用户访问 `http://框架地址/images/aaa.jpg` 就可以快速地调用此路由下的局部文件服务器功能了。