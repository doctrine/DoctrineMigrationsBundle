<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsRepository;

use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\Version;

use function array_values;

/** @internal */
final class CompositeMigrationsRepository implements MigrationsRepository
{
    /** @var list<MigrationsRepository> */
    private $migrationRepositories;

    /** @var bool */
    private $migrationsLoaded = false;

    /** @var array<string, AvailableMigration> */
    private $migrations = [];

    public function __construct(MigrationsRepository ...$migrationRepositories)
    {
        $this->migrationRepositories = array_values($migrationRepositories);
    }

    public function hasMigration(string $version): bool
    {
        foreach ($this->migrationRepositories as $migrationRepository) {
            if ($migrationRepository->hasMigration($version)) {
                return true;
            }
        }

        return false;
    }

    public function getMigration(Version $version): AvailableMigration
    {
        $this->loadMigrations();

        $id = (string) $version;

        if (! isset($this->migrations[$id])) {
            throw MigrationClassNotFound::new($id);
        }

        return $this->migrations[$id];
    }

    public function getMigrations(): AvailableMigrationsSet
    {
        $this->loadMigrations();

        return new AvailableMigrationsSet($this->migrations);
    }

    private function loadMigrations(): void
    {
        if ($this->migrationsLoaded) {
            return;
        }

        $this->migrationsLoaded = true;

        foreach ($this->migrationRepositories as $migrationRepository) {
            foreach ($migrationRepository->getMigrations()->getItems() as $migration) {
                $id                    = (string) $migration->getVersion();
                $this->migrations[$id] = $this->migrations[$id] ?? $migration;
            }
        }
    }
}
