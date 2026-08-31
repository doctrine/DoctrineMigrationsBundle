<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\MigrationsRepository;

use Doctrine\Bundle\MigrationsBundle\MigrationsRepository\ServiceMigrationsRepository;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ServiceLocatorTrait;
use Symfony\Contracts\Service\ServiceProviderInterface;

final class ServiceMigrationsRepositoryTest extends TestCase
{
    public function testHasMigration(): void
    {
        $container = new class ([
            'Version001' => static fn () => self::fail('This should not be called.'),
        ]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $repository = new ServiceMigrationsRepository($container);

        self::assertTrue($repository->hasMigration('Version001'));
    }

    public function testDoesNotHaveMigration(): void
    {
        $container = new class ([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $repository = new ServiceMigrationsRepository($container);

        self::assertFalse($repository->hasMigration('Version001'));
    }

    public function testGetMigrationReturnsAvailableMigration(): void
    {
        $migration = self::createStub(AbstractMigration::class);

        $container = new class ([
            'Version001' => static fn () => $migration,
        ]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $repository = new ServiceMigrationsRepository($container);
        $version    = new Version('Version001');

        $availableMigration = $repository->getMigration($version);

        self::assertSame($version, $availableMigration->getVersion());
        self::assertSame($migration, $availableMigration->getMigration());
    }

    public function testGetMigrationThrowsExceptionWhenMigrationNotFound(): void
    {
        $container = new class ([]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $repository = new ServiceMigrationsRepository($container);
        $version    = new Version('NonExistentVersion');

        $this->expectException(MigrationClassNotFound::class);

        $repository->getMigration($version);
    }

    public function testGetMigrationsReturnsAvailableMigrationsSet(): void
    {
        $container = new class ([
            'Version001' => static fn () => self::createStub(AbstractMigration::class),
            'Version002' => static fn () => self::createStub(AbstractMigration::class),
        ]) implements ServiceProviderInterface {
            use ServiceLocatorTrait;
        };

        $repository = new ServiceMigrationsRepository($container);

        $migrationsSet = $repository->getMigrations();

        self::assertCount(2, $migrationsSet->getItems());
    }
}
