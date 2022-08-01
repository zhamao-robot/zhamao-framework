<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationReader;
use Koriym\Attributes\AttributeReader;
use Koriym\Attributes\DualReader;
use ZM\Annotation\Framework\OnSetup;
use ZM\ConsoleApplication;
use ZM\Exception\InitException;
use ZM\Store\FileSystem;

function _zm_setup_loader()
{
    try {
        try {
            new ConsoleApplication('zhamao');
        } catch (InitException $e) {
        }
        $base_path = SOURCE_ROOT_DIR;
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
            $all_event_class = array_merge($all_event_class, FileSystem::getClassesPsr4($autoload_path, $namespace));
        }

        $reader = new DualReader(new AnnotationReader(), new AttributeReader());
        $event_list = [];
        $setup_list = [];
        foreach ($all_event_class as $v) {
            $reflection_class = new ReflectionClass($v);
            $methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $vs) {
                $method_annotations = $reader->getMethodAnnotations($vs);
                if ($method_annotations != []) {
                    $annotation = $method_annotations[0];
                    if ($annotation instanceof OnSetup) {
                        $setup_list[] = [
                            'class' => $v,
                            'method' => $vs->getName(),
                        ];
                    }
                }
            }
        }
        return json_encode(['setup' => $setup_list, 'event' => $event_list]);
    } catch (Throwable $e) {
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, zm_internal_errcode('E00031') . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine() . PHP_EOL);
        fclose($stderr);
        exit(1);
    }
}

// 在*nix等支持多进程环境的情况，可直接运行此文件，那么就执行
if (debug_backtrace() === []) {
    require((!is_dir(__DIR__ . '/../../vendor')) ? getcwd() : (__DIR__ . '/../..')) . '/vendor/autoload.php';
    echo _zm_setup_loader();
}
