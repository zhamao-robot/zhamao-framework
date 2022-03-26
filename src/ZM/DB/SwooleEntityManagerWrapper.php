<?php

declare(strict_types=1);

namespace ZM\DB;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Setup;
use ZM\Console\Console;
use ZM\Utils\DataProvider;

class SwooleEntityManagerWrapper
{
    /**
     * 协程与 EntityManager 的绑定
     *
     * @var array{int, EntityManagerInterface}
     */
    private $entityManagers = [];

    /**
     * 将所有调用转向内部 EntityManager
     *
     * @param $name
     * @param $arguments
     * @throws ORMException
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->getWrappedEntityManager()->{$name}(...$arguments);
    }

    /**
     * 获取包装后的 EntityManager
     *
     * @throws ORMException
     */
    private function getWrappedEntityManager(): EntityManagerInterface
    {
        // 尝试获取当前协程的 EntityManager
        $entity_manager = $this->entityManagers[zm_cid()] ?? null;

        if (is_null($entity_manager)) {
            // 如果没有，则创建一个新的 EntityManager
            Console::debug('Creating new EntityManager');

            // useSimpleAnnotationReader 必须为 false，否则无法正常解析
            // 其他选项考虑提供配置项
            // TODO: 扫描所有的 Model 目录
            $config = Setup::createAnnotationMetadataConfiguration([DataProvider::getSourceRootDir() . '/src/Module/Model'], false, null, null, false);

            // TODO: 允许用户配置驱动
            $conn = [
                'driver' => 'pdo_sqlite',
                'path' => DataProvider::getSourceRootDir() . '/db.sqlite',
            ];

            $entity_manager = EntityManager::create($conn, $config);
            $this->entityManagers[zm_cid()] = $entity_manager;
            // 在协程结束时，自动关闭并清除 EntityManager 实例
            defer(function () use ($entity_manager) {
                Console::debug('Closing EntityManager');
                $entity_manager->close();
                unset($this->entityManagers[zm_cid()], $entity_manager);
                gc_collect_cycles();
            });
        }

        return $entity_manager;
    }
}
