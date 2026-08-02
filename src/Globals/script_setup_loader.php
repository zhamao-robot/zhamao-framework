<?php

declare(strict_types=1);

use ZM\Annotation\AnnotationParser;
use ZM\Annotation\Framework\Setup;
use ZM\Utils\ZMUtil;

function _zm_setup_loader()
{
    try {
        global $_tmp_setup_list;
        $_tmp_setup_list = [];
        $parser = new AnnotationParser(false);
        $composer = ZMUtil::getComposerMetadata();
        // 合并 dev 和 非 dev 的 psr-4 加载目录
        $merge_psr4 = array_merge($composer['autoload']['psr-4'] ?? [], $composer['autoload-dev']['psr-4'] ?? []);
        // 排除 composer.json 中指定需要排除的目录
        $excludes = $composer['extra']['zm']['exclude-annotation-path'] ?? [];
        foreach ($merge_psr4 as $k => $v) {
            // 如果在排除表就排除，否则就解析注解
            if (is_dir(SOURCE_ROOT_DIR . '/' . $v) && !in_array($v, $excludes)) {
                // 添加解析路径，对应Base命名空间也贴出来
                $parser->addPsr4Path(SOURCE_ROOT_DIR . '/' . $v . '/', trim($k, '\\'));
            }
        }
        $parser->addSpecialParser(Setup::class, function (Setup $setup) {
            global $_tmp_setup_list;
            $_tmp_setup_list[] = [
                'class' => $setup->class,
                'method' => $setup->method,
            ];
            return true;
        });

        // 这里好像没必要加载插件目录的插件，插件理论上不能在 Master 被加载

        // 解析所有注册路径的文件，获取注解
        $parser->parse();

        return json_encode(['setup' => $_tmp_setup_list]);
    } catch (Throwable $e) {
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, zm_internal_errcode('E00031') . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine() . PHP_EOL);
        fclose($stderr);
        exit(1);
    }
}

// 在*nix等支持多进程环境的情况，可直接运行此文件，那么就执行
if (debug_backtrace() === []) {
    // 优先使用当前工作目录的 vendor（被框架 exec 调用时 cwd 为项目根目录），
    // 以兼容通过 Composer path 仓库 symlink 安装框架的场景——此时 __DIR__ 会解析到框架源码真实目录，
    // 若据此查找 vendor 会错误地加载框架自身的依赖而非宿主项目的依赖
    $autoload = getcwd() . '/vendor/autoload.php';
    if (!file_exists($autoload) && is_dir(__DIR__ . '/../../vendor')) {
        // 兜底：直接从框架源码目录运行时的依赖加载
        $autoload = __DIR__ . '/../../vendor/autoload.php';
    }
    require $autoload;
    echo _zm_setup_loader();
}
