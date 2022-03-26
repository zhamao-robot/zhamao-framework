<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;

require_once 'vendor/autoload.php';

// TODO: 使用与 SwooleEntityManagerWrapper 一致的配置

$config = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/src/Module/Model'], false, null, null, false);

$conn = [
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/db.sqlite',
];

$entity_manager = EntityManager::create($conn, $config);

return ConsoleRunner::createHelperSet($entity_manager);

