<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\MigrationsRepository;

use Doctrine\Bundle\MigrationsBundle\MigrationsRepository\ServiceMigrationFactory;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ServiceProviderInterface;

class ServiceMigrationFactoryTest extends TestCase
{
    public function testReturnsTheServiceWhenTheMigrationIsRegisteredAsAService(): void
    {
        $migration = $this->createMock(AbstractMigration::class);

        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('has')
            ->with('Version001')
            ->willReturn(true);
        $container->method('get')
            ->with('Version001')
            ->willReturn($migration);

        $decorated = $this->createMock(MigrationFactory::class);
        $decorated->expects(self::never())
            ->method('createVersion');

        $factory = new ServiceMigrationFactory($container, $decorated);

        self::assertSame($migration, $factory->createVersion('Version001'));
    }

    public function testDelegatesToTheDecoratedFactoryOtherwise(): void
    {
        $migration = $this->createMock(AbstractMigration::class);

        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('has')
            ->with('Version001')
            ->willReturn(false);
        $container->expects(self::never())
            ->method('get');

        $decorated = $this->createMock(MigrationFactory::class);
        $decorated->expects(self::once())
            ->method('createVersion')
            ->with('Version001')
            ->willReturn($migration);

        $factory = new ServiceMigrationFactory($container, $decorated);

        self::assertSame($migration, $factory->createVersion('Version001'));
    }
}
