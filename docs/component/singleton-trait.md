# 单例类（SingletonTrait）

单例类，顾名思义，就是让用户声明的类拥有单例的特性，而这一组件引入的方式也最直接。它是一个 PHP 的 `trait`。

我们传统写单例类的方式很手动，比如下面这样：

```php
<?php
    
class Foo {
    public $test = 0;
    private static $instance;
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new Foo();
        }

        return self::$instance;
    }
}
Foo::getInstance()->test = 4;
$obj = Foo::getInstance()->test;
var_dump($obj); // 4
```

这就要求我们每个需要声明为单例的类都写一个成员静态方法和成员静态变量。

框架使用了 PHP Trait 来快速让一个类支持这一特性：

```php
<?php
    
use ZM\Utils\SingletonTrait;
class Foo {
    use SingletonTrait;
    public $test = 0;
}

Foo::getInstance()->test = 5;
var_dump(Foo::getInstance()->test);
```

只需要在类中使用：`use \ZM\Utils\SingletonTrait;` 一句话即可。