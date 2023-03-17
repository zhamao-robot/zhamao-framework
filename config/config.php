<?php

declare(strict_types=1);

/**
 * Config 配置类的配置文件
 * 由于 Config 类是第一批被加载的类，因此本文件存在以下限制：
 * 1. 只能使用 PHP 格式
 * 2. 无法利用容器及依赖注入
 * 3. 必须存在于本地，无法使用远程配置（后续版本可能会支持）
 */
return [
    'repository' => [
        \OneBot\Config\Repository::class, // 配置仓库，须实现 \OneBot\Config\RepositoryInterface 接口
        [], // 传入的参数，依序传入构造函数
    ],
    'loader' => [
        \OneBot\Config\Loader\DelegateLoader::class, // 配置加载器，须实现 \OneBot\Config\LoaderInterface 接口
        [], // 传入的参数，依序传入构造函数
    ],
    'source' => [
        'extensions' => ['php', 'yaml', 'yml', 'json', 'toml'], // 配置文件扩展名
        'paths' => [
            \ZM\Framework::getInstance()->runtime_preferences->getConfigDir(), // 配置文件所在目录
            // 可以添加多个配置文件目录
        ],
    ],
    'trace' => false, // 是否开启配置跟踪器
];
