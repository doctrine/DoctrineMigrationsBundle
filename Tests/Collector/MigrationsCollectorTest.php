<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Collector;

use Doctrine\Bundle\MigrationsBundle\Collector\MigrationsCollector;
use Doctrine\Bundle\MigrationsBundle\Collector\MigrationsFlattener;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MigrationsCollectorTest extends TestCase
{
    public function testCollectWithoutActiveConnection(): void
    {
        $dependencyFactory   = $this->createMock(DependencyFactory::class);
        $migrationsFlattener = $this->createMock(MigrationsFlattener::class);
        $request             = $this->createMock(Request::class);
        $response            = $this->createMock(Response::class);
        $metadataStorage     = $this->createMock(MetadataStorage::class);
        $connection          = $this->createMock(Connection::class);

        $dependencyFactory
            ->method('getConnection')
            ->willReturn($connection);

        $connection
            ->method('isConnected')
            ->willReturn(false);

        $dependencyFactory
            ->expects($this->never())
            ->method('getMetadataStorage');

        $dependencyFactory
            ->expects($this->never())
            ->method('getMigrationPlanCalculator');

        $metadataStorage
            ->expects($this->never())
            ->method('getExecutedMigrations');

        $target = new MigrationsCollector($dependencyFactory, $migrationsFlattener);
        $target->collect($request, $response);
    }
}
