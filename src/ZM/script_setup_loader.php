<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationReader;
use ZM\Annotation\Swoole\OnSetup;
use ZM\Annotation\Swoole\SwooleHandler;
use ZM\ConsoleApplication;
use ZM\Exception\InitException;
use ZM\Utils\DataProvider;
use ZM\Utils\ZMUtil;

require_once((!is_dir(__DIR__ . '/../../vendor')) ? getcwd() : (__DIR__ . '/../..')) . '/vendor/autoload.php';

try {
    try {
        (new ConsoleApplication('zhamao'))->initEnv();
    } catch (InitException $e) {
    }
    $base_path = DataProvider::getSourceRootDir();
    $scan_paths = [];
    $composer = json_decode(file_get_contents($base_path . '/composer.json'), true);
    $exclude_annotations = array_merge($composer['extra']['exclude_annotate'] ?? [], $composer['extra']['zm']['exclude-annotation-path'] ?? []);
    foreach (($composer['autoload']['psr-4'] ?? []) as $k => $v) {
        if (is_dir($base_path . '/' . $v) && !in_array($v, $exclude_annotations)) {
            $scan_paths[trim($k, '\\')] = $base_path . '/' . $v;
        }
    }
    foreach (($composer['autoload-dev']['psr-4'] ?? []) as $k => $v) {
        if (is_dir($base_path . '/' . $v) && !in_array($v, $exclude_annotations)) {
            $scan_paths[trim($k, '\\')] = $base_path . '/' . $v;
        }
    }
    $all_event_class = [];
    foreach ($scan_paths as $namespace => $autoload_path) {
        $all_event_class = array_merge($all_event_class, ZMUtil::getClassesPsr4($autoload_path, $namespace));
    }

    $reader = new AnnotationReader();
    $event_list = [];
    $setup_list = [];
    foreach ($all_event_class as $v) {
        $reflection_class = new ReflectionClass($v);
        $methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $vs) {
            $method_annotations = $reader->getMethodAnnotations($vs);
            if ($method_annotations != []) {
                $annotation = $method_annotations[0];
                if ($annotation instanceof SwooleHandler) {
                    $event_list[] = [
                        'class' => $v,
                        'method' => $vs->getName(),
                        'event' => $annotation->event,
                    ];
                } elseif ($annotation instanceof OnSetup) {
                    $setup_list[] = [
                        'class' => $v,
                        'method' => $vs->getName(),
                    ];
                }
            }
        }
    }
    echo json_encode(['setup' => $setup_list, 'event' => $event_list]);
} catch (Throwable $e) {
    $stderr = fopen('php://stderr', 'w');
    fwrite($stderr, zm_internal_errcode('E00031') . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine() . PHP_EOL);
    fclose($stderr);
    exit(1);
}

/*
记迷惑，这里的代码是不是可以放到一个单独的文件里面，这样就不会出现每次都要重新加载的问题了？
然后这个文件就实现了，就是这个。
但是还有个什么问题呢？为了 reload 牺牲了太多太多，但是关键时刻好像又不是很能用到。
但又不能没有。
所以我很纠结很纠结。
如何让用户的代码能像 php-fpm 那样随时重置呢？
我不知道诶。
那这段代码干了个啥？
在最开始单独启动进程，加载一遍所有类，获取需要在启动前就执行的类，然后在启动的时候执行。
这样就可以不在爷进程里面加载所有类，在爹进程里面 Fork 的子进程再加载所有类，每次 reload 时可以重新加载了。
以上均为乱写的，请勿完全当真，本人对待框架代码还是比较认真的。
*/
