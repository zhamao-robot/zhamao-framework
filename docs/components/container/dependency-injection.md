# 依赖注入

依赖注入这个名词并没有你想象的那么复杂，实际上它就是把一个类的依赖通过构造函数或者 setter
方法注入到类中。这样做的好处是，你可以在不修改类的情况下，通过修改容器定义来改变依赖的值。

下方是一个简单的例子，它展示了如何使用依赖注入来获取框架提供的依赖：

```php
<?php

use ZM\Context\BotContext;

class SampleModule
{
    #[BotCommand('hello', 'hello')]
    public function sayHello(BotContext $context): void
    {
        $context->reply($event->getMessage());
    }
}
```

在上面的例子中，`BotContext` 类是一个依赖，它通过依赖注入注入到 `sayHello` 方法中。

另一方面，你也可以利用依赖注入来注入你自己的依赖：

```php
<?php

use MyApp\UserRepositoryInterface;

class SampleModule
{
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
}
```

在上面的例子中，`UserRepositoryInterface` 是一个接口，它的实现类是通过构造函数注入到 `SampleModule` 类中的。

`UserRepositoryInterface`
定义了从数据库中获取用户信息的方法。对于调用者来说，它并不关心具体的实现类是什么，只需要知道它实现了 `UserRepositoryInterface`
接口即可。

因此，我们可以轻易地切换 `UserRepositoryInterface` 的实现类，而不需要修改 `SampleModule`
类。此外，当需要为该类编写测试时，我们也可以很轻松地模拟或是创建一个伪实现来操作。

## 零配置依赖注入

如果一个类没有依赖项或只依赖于已经在容器中定义的类，那么你可以不用做任何配置就可以使用依赖注入解析它。

例如：

```php
<?php

class TestService
{
    public function __construct()
    {
        // ...
    }
}
```

在上面的例子中，`TestService` 类没有依赖项，因此你可以直接使用依赖注入来解析它：

```php
<?php

use TestService;

class SampleModule
{
    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }
}
```

## 何时使用依赖注入

我们尽力在框架的大部分地方都支持使用依赖注入，例如命令、路由、中间件等。你可以在这些地方使用依赖注入来获取依赖。
