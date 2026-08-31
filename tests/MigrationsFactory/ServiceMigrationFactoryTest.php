<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\MigrationsFactory;

use Doctrine\Bundle\MigrationsBundle\MigrationsFactory\ServiceMigrationFactory;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ServiceLocatorTrait;
use Symfony\Contracts\Service\ServiceProviderInterface;

final class ServiceMigrationFactoryTest extends TestCase
{
    public function testCreateVersionReturnsMigrationFromContainer(): void
    {
        $migration = self::createStub(AbstractMigration::class);

        $container = new class ([
            'Version001' => static fn () => $migration,
        ]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $decoratedFactory = $this->createMock(MigrationFactory::class);
        $decoratedFactory->expects(self::never())
            ->method('createVersion');

        $factory = new ServiceMigrationFactory($decoratedFactory, $container);

        self::assertSame($migration, $factory->createVersion('Version001'));
    }

    public function testCreateVersionDelegatesToDecoratedFactoryWhenMigrationIsNotAService(): void
    {
        $migration = self::createStub(AbstractMigration::class);

        $container = new class ([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $decoratedFactory = $this->createMock(MigrationFactory::class);
        $decoratedFactory->expects(self::once())
            ->method('createVersion')
            ->with('Version001')
            ->willReturn($migration);

        $factory = new ServiceMigrationFactory($decoratedFactory, $container);

        self::assertSame($migration, $factory->createVersion('Version001'));
    }
}
