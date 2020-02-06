<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Fixtures;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class CustomEntityManager implements EntityManagerInterface
{
    public function getCache() : void
    {
    }

    public function getConnection()
    {
        return new class ($this) extends Connection {
            /** @var CustomEntityManager */
            private $em;

            public function __construct(CustomEntityManager $em)
            {
                $this->em = $em;
            }

            public function getEm()
            {
                return $this->em;
            }
        };
    }

    public function getExpressionBuilder() : void
    {
    }

    public function beginTransaction() : void
    {
    }

    public function transactional($func) : void
    {
    }

    public function commit() : void
    {
    }

    public function rollback() : void
    {
    }

    public function createQuery($dql = '') : void
    {
    }

    public function createNamedQuery($name) : void
    {
    }

    public function createNativeQuery($sql, ResultSetMapping $rsm) : void
    {
    }

    public function createNamedNativeQuery($name) : void
    {
    }

    public function createQueryBuilder() : void
    {
    }

    public function getReference($entityName, $id) : void
    {
    }

    public function getPartialReference($entityName, $identifier) : void
    {
    }

    public function close() : void
    {
    }

    public function copy($entity, $deep = false) : void
    {
    }

    public function lock($entity, $lockMode, $lockVersion = null) : void
    {
    }

    public function getEventManager() : void
    {
    }

    public function getConfiguration() : void
    {
    }

    public function isOpen() : void
    {
    }

    public function getUnitOfWork() : void
    {
    }

    public function getHydrator($hydrationMode) : void
    {
    }

    public function newHydrator($hydrationMode) : void
    {
    }

    public function getProxyFactory() : void
    {
    }

    public function getFilters() : void
    {
    }

    public function isFiltersStateClean() : void
    {
    }

    public function hasFilters() : void
    {
    }

    public function find($className, $id) : void
    {
    }

    public function persist($object) : void
    {
    }

    public function remove($object) : void
    {
    }

    public function merge($object) : void
    {
    }

    public function clear($objectName = null) : void
    {
    }

    public function detach($object) : void
    {
    }

    public function refresh($object) : void
    {
    }

    public function flush() : void
    {
    }

    public function getRepository($className) : void
    {
    }

    public function getMetadataFactory() : void
    {
    }

    public function initializeObject($obj) : void
    {
    }

    public function contains($object) : void
    {
    }

    public function getClassMetadata($className) : void
    {
    }
}
