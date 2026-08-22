<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\MigrationsRepository;

use Doctrine\Bundle\MigrationsBundle\MigrationsRepository\ServiceMigrationsRepository;
use Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\Migrations\Migration001;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
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

    public function testFallsBackToTheConfiguredMigrationsForMigrationsNotRegisteredAsServices(): void
    {
        $serviceMigration    = $this->createMock(AbstractMigration::class);
        $filesystemMigration = new Migration001($this->createMock(Connection::class), new NullLogger());

        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('getProvidedServices')
            ->willReturn(['Version001' => '?']);
        $container->method('has')
            ->willReturnCallback(static function (string $id): bool {
                return $id === 'Version001';
            });
        $container->method('get')
            ->with('Version001')
            ->willReturn($serviceMigration);

        $configuration = new Configuration();
        $configuration->addMigrationsDirectory('Doctrine\\Bundle\\MigrationsBundle\\Tests\\Fixtures\\Migrations', __DIR__ . '/../Fixtures/Migrations');

        $finder = $this->createMock(MigrationFinder::class);
        $finder->expects(self::once())
            ->method('findMigrations')
            ->with(__DIR__ . '/../Fixtures/Migrations', 'Doctrine\\Bundle\\MigrationsBundle\\Tests\\Fixtures\\Migrations')
            ->willReturn([Migration001::class, 'Version001']);

        $factory = $this->createMock(MigrationFactory::class);
        $factory->expects(self::once())
            ->method('createVersion')
            ->with(Migration001::class)
            ->willReturn($filesystemMigration);

        $repository = new ServiceMigrationsRepository($container, $configuration, $finder, $factory);

        self::assertTrue($repository->hasMigration('Version001'));
        self::assertTrue($repository->hasMigration(Migration001::class));
        self::assertFalse($repository->hasMigration('Version002'));

        self::assertSame($serviceMigration, $repository->getMigration(new Version('Version001'))->getMigration());
        self::assertSame($filesystemMigration, $repository->getMigration(new Version(Migration001::class))->getMigration());

        $migrationsSet = $repository->getMigrations();

        self::assertCount(2, $migrationsSet->getItems());
        self::assertSame($serviceMigration, $migrationsSet->getMigration(new Version('Version001'))->getMigration());
        self::assertSame($filesystemMigration, $migrationsSet->getMigration(new Version(Migration001::class))->getMigration());
    }

    public function testFallbackThrowsExceptionWhenMigrationClassDoesNotExist(): void
    {
        $container = $this->createMock(ServiceProviderInterface::class);
        $container->method('getProvidedServices')
            ->willReturn([]);
        $container->method('has')
            ->willReturn(false);

        $configuration = new Configuration();
        $configuration->addMigrationClass('NonExistentVersion');

        $repository = new ServiceMigrationsRepository(
            $container,
            $configuration,
            $this->createMock(MigrationFinder::class),
            $this->createMock(MigrationFactory::class)
        );

        $this->expectException(MigrationClassNotFound::class);

        $repository->getMigrations();
    }
}
