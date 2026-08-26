<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\MigrationsRepository;

use Doctrine\Bundle\MigrationsBundle\MigrationsRepository\ServiceMigrationsRepository;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ServiceProviderInterface;

class ServiceMigrationsRepositoryTest extends TestCase
{
    /**
     * @testWith [true]
     *           [false]
     */
    public function testHasMigration(bool $expectedResult): void
    {
        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('has')
            ->with('Version001')
            ->willReturn($expectedResult);

        $repository = new ServiceMigrationsRepository($container);

        self::assertSame($expectedResult, $repository->hasMigration('Version001'));
    }

    public function testGetMigrationReturnsAvailableMigration(): void
    {
        $migration = $this->createMock(AbstractMigration::class);

        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('has')
            ->with('Version001')
            ->willReturn(true);
        $container->method('get')
            ->with('Version001')
            ->willReturn($migration);

        $repository = new ServiceMigrationsRepository($container);
        $version    = new Version('Version001');

        $availableMigration = $repository->getMigration($version);

        self::assertSame($version, $availableMigration->getVersion());
        self::assertSame($migration, $availableMigration->getMigration());
    }

    public function testGetMigrationThrowsExceptionWhenMigrationNotFound(): void
    {
        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('has')
            ->with('NonExistentVersion')
            ->willReturn(false);

        $repository = new ServiceMigrationsRepository($container);
        $version    = new Version('NonExistentVersion');

        $this->expectException(MigrationClassNotFound::class);

        $repository->getMigration($version);
    }

    public function testGetMigrationsReturnsAvailableMigrationsSet(): void
    {
        $migration1 = $this->createMock(AbstractMigration::class);
        $migration2 = $this->createMock(AbstractMigration::class);

        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('getProvidedServices')
            ->willReturn(['Version001' => '?', 'Version002' => '?']);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturnMap([
                ['Version001', $migration1],
                ['Version002', $migration2],
            ]);

        $repository = new ServiceMigrationsRepository($container);

        $migrationsSet = $repository->getMigrations();

        self::assertCount(2, $migrationsSet->getItems());
    }
}
