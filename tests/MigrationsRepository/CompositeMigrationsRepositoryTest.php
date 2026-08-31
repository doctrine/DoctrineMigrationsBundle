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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class CompositeMigrationsRepositoryTest extends TestCase
{
    #[TestWith([true, true])]
    #[TestWith([true, false])]
    #[TestWith([false, true])]
    public function testHasMigrationReturnsTrueWhenAnyRepositoryHasIt(bool $first, bool $second): void
    {
        $repository = new CompositeMigrationsRepository(
            $first ? $this->createRepositoryHavingMigration() : $this->createEmptyRepository(),
            $second ? $this->createRepositoryHavingMigration() : $this->createEmptyRepository(),
        );

        self::assertTrue($repository->hasMigration('Version001'));
    }

    public function testHasMigrationReturnsFalseWhenNoRepositoryHasIt(): void
    {
        $repository = new CompositeMigrationsRepository(
            $this->createEmptyRepository(),
            $this->createEmptyRepository(),
        );

        self::assertFalse($repository->hasMigration('Version001'));
    }

    public function testGetMigrationReturnsMigrationFromAnyRepository(): void
    {
        $migration1 = new AvailableMigration(new Version('Version001'), self::createStub(AbstractMigration::class));
        $migration2 = new AvailableMigration(new Version('Version002'), self::createStub(AbstractMigration::class));

        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryWithMigrations($migration1),
            $this->createRepositoryWithMigrations($migration2),
        );

        self::assertSame($migration1, $repository->getMigration(new Version('Version001')));
        self::assertSame($migration2, $repository->getMigration(new Version('Version002')));
    }

    public function testGetMigrationPrefersEarlierRepositoriesOnDuplicateVersions(): void
    {
        $migration1 = new AvailableMigration(new Version('Version001'), self::createStub(AbstractMigration::class));
        $migration2 = new AvailableMigration(new Version('Version001'), self::createStub(AbstractMigration::class));

        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryWithMigrations($migration1),
            $this->createRepositoryWithMigrations($migration2),
        );

        self::assertSame($migration1, $repository->getMigration(new Version('Version001')));
    }

    public function testGetMigrationThrowsExceptionWhenMigrationNotFound(): void
    {
        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryWithMigrations(),
            $this->createRepositoryWithMigrations(),
        );

        $this->expectException(MigrationClassNotFound::class);

        $repository->getMigration(new Version('NonExistentVersion'));
    }

    public function testGetMigrationsMergesAllRepositories(): void
    {
        $migration1 = new AvailableMigration(new Version('Version001'), self::createStub(AbstractMigration::class));
        $migration2 = new AvailableMigration(new Version('Version001'), self::createStub(AbstractMigration::class));
        $migration3 = new AvailableMigration(new Version('Version002'), self::createStub(AbstractMigration::class));

        $repository = new CompositeMigrationsRepository(
            $this->createRepositoryWithMigrations($migration1),
            $this->createRepositoryWithMigrations($migration2, $migration3),
        );

        $migrationsSet = $repository->getMigrations();

        self::assertCount(2, $migrationsSet->getItems());
        self::assertSame($migration1, $migrationsSet->getMigration(new Version('Version001')));
        self::assertSame($migration3, $migrationsSet->getMigration(new Version('Version002')));
    }

    public function testRepositoriesAreLoadedOnlyOnce(): void
    {
        $migration = new AvailableMigration(new Version('Version001'), self::createStub(AbstractMigration::class));

        $innerRepository = $this->createMock(MigrationsRepository::class);
        $innerRepository->expects(self::once())
            ->method('getMigrations')
            ->willReturn(new AvailableMigrationsSet([$migration]));

        $repository = new CompositeMigrationsRepository($innerRepository);

        $repository->getMigrations();
        $repository->getMigrations();
        $repository->getMigration(new Version('Version001'));
    }

    private function createRepositoryHavingMigration(): MigrationsRepository
    {
        return new readonly class (self::createStub(AbstractMigration::class)) implements MigrationsRepository
        {
            public function __construct(
                private AbstractMigration $migration,
            ) {
            }

            public function hasMigration(string $version): bool
            {
                return $version === 'Version001';
            }

            public function getMigration(Version $version): AvailableMigration
            {
                if ((string) $version !== 'Version001') {
                    throw MigrationClassNotFound::new((string) $version);
                }

                return new AvailableMigration($version, $this->migration);
            }

            public function getMigrations(): AvailableMigrationsSet
            {
                return new AvailableMigrationsSet([new AvailableMigration(new Version('Version001'), $this->migration)]);
            }
        };
    }

    private function createEmptyRepository(): MigrationsRepository
    {
        return new readonly class implements MigrationsRepository
        {
            public function hasMigration(string $version): false
            {
                return false;
            }

            public function getMigration(Version $version): never
            {
                throw MigrationClassNotFound::new((string) $version);
            }

            public function getMigrations(): AvailableMigrationsSet
            {
                return new AvailableMigrationsSet([]);
            }
        };
    }

    private function createRepositoryWithMigrations(AvailableMigration ...$migrations): MigrationsRepository
    {
        $repository = self::createStub(MigrationsRepository::class);
        $repository->method('getMigrations')
            ->willReturn(new AvailableMigrationsSet($migrations));

        return $repository;
    }
}
