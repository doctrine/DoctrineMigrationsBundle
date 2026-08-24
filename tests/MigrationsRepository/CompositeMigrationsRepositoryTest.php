<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\MigrationsRepository;

use Doctrine\Bundle\MigrationsBundle\MigrationsRepository\CompositeMigrationsRepository;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;

class CompositeMigrationsRepositoryTest extends TestCase
{
    /**
     * @testWith [true, true]
     *           [true, false]
     *           [false, true]
     */
    public function testHasMigrationReturnsTrueWhenAnyRepositoryHasIt(bool $first, bool $second): void
    {
        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryHavingMigration($first),
            $this->createRepositoryHavingMigration($second)
        );

        self::assertTrue($repository->hasMigration('Version001'));
    }

    public function testHasMigrationReturnsFalseWhenNoRepositoryHasIt(): void
    {
        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryHavingMigration(false),
            $this->createRepositoryHavingMigration(false)
        );

        self::assertFalse($repository->hasMigration('Version001'));
    }

    public function testGetMigrationReturnsMigrationFromAnyRepository(): void
    {
        $migration1 = new AvailableMigration(new Version('Version001'), $this->createMock(AbstractMigration::class));
        $migration2 = new AvailableMigration(new Version('Version002'), $this->createMock(AbstractMigration::class));

        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryWithMigrations($migration1),
            $this->createRepositoryWithMigrations($migration2)
        );

        self::assertSame($migration1, $repository->getMigration(new Version('Version001')));
        self::assertSame($migration2, $repository->getMigration(new Version('Version002')));
    }

    public function testGetMigrationPrefersEarlierRepositoriesOnDuplicateVersions(): void
    {
        $migration1 = new AvailableMigration(new Version('Version001'), $this->createMock(AbstractMigration::class));
        $migration2 = new AvailableMigration(new Version('Version001'), $this->createMock(AbstractMigration::class));

        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryWithMigrations($migration1),
            $this->createRepositoryWithMigrations($migration2)
        );

        self::assertSame($migration1, $repository->getMigration(new Version('Version001')));
    }

    public function testGetMigrationThrowsExceptionWhenMigrationNotFound(): void
    {
        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryWithMigrations(),
            $this->createRepositoryWithMigrations()
        );

        $this->expectException(MigrationClassNotFound::class);

        $repository->getMigration(new Version('NonExistentVersion'));
    }

    public function testGetMigrationsMergesAllRepositories(): void
    {
        $migration1 = new AvailableMigration(new Version('Version001'), $this->createMock(AbstractMigration::class));
        $migration2 = new AvailableMigration(new Version('Version001'), $this->createMock(AbstractMigration::class));
        $migration3 = new AvailableMigration(new Version('Version002'), $this->createMock(AbstractMigration::class));

        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryWithMigrations($migration1),
            $this->createRepositoryWithMigrations($migration2, $migration3)
        );

        $migrationsSet = $repository->getMigrations();

        self::assertCount(2, $migrationsSet->getItems());
        self::assertSame($migration1, $migrationsSet->getMigration(new Version('Version001')));
        self::assertSame($migration3, $migrationsSet->getMigration(new Version('Version002')));
    }

    public function testRepositoriesAreLoadedOnlyOnce(): void
    {
        $migration = new AvailableMigration(new Version('Version001'), $this->createMock(AbstractMigration::class));

        $innerRepository = $this->createMock(MigrationsRepository::class);
        $innerRepository->expects(self::once())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsSet([$migration]));

        $repository = new CompositeMigrationsRepository($innerRepository);

        $repository->getMigrations();
        $repository->getMigrations();
        $repository->getMigration(new Version('Version001'));
    }

    private function createRepositoryHavingMigration(bool $hasMigration): MigrationsRepository
    {
        $repository = $this->createMock(MigrationsRepository::class);
        $repository->method('hasMigration')
            ->with('Version001')
            ->willReturn($hasMigration);

        return $repository;
    }

    private function createRepositoryWithMigrations(AvailableMigration ...$migrations): MigrationsRepository
    {
        $repository = $this->createMock(MigrationsRepository::class);
        $repository->method('getMigrations')
            ->willReturn(new AvailableMigrationsSet($migrations));

        return $repository;
    }
}
