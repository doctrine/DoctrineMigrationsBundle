<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\MigrationsFactory;

use Doctrine\Bundle\MigrationsBundle\MigrationsFactory\ServiceMigrationFactory;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ServiceProviderInterface;

class ServiceMigrationFactoryTest extends TestCase
{
    public function testCreateVersionReturnsMigrationFromContainer(): void
    {
        $migration = self::createStub(AbstractMigration::class);

        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('has')
            ->with('Version001')
            ->willReturn(true);
        $container->method('get')
            ->with('Version001')
            ->willReturn($migration);

        $decoratedFactory = $this->createMock(MigrationFactory::class);
        $decoratedFactory->expects(self::never())
            ->method('createVersion');

        $factory = new ServiceMigrationFactory($decoratedFactory, $container);

        self::assertSame($migration, $factory->createVersion('Version001'));
    }

    public function testCreateVersionDelegatesToDecoratedFactoryWhenMigrationIsNotAService(): void
    {
        $migration = self::createStub(AbstractMigration::class);

        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('has')
            ->with('Version001')
            ->willReturn(false);
        $container->expects(self::never())
            ->method('get');

        $decoratedFactory = $this->createMock(MigrationFactory::class);
        $decoratedFactory->expects(self::once())
            ->method('createVersion')
            ->with('Version001')
            ->willReturn($migration);

        $factory = new ServiceMigrationFactory($decoratedFactory, $container);

        self::assertSame($migration, $factory->createVersion('Version001'));
    }
}
