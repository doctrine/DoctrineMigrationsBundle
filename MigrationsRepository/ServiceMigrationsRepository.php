<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsRepository;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\DuplicateMigrationVersion;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\Version;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function assert;

class ServiceMigrationsRepository implements MigrationsRepository
{
    /** @var MigrationsRepository */
    private $migrationRepository;

    /** @var ServiceProviderInterface */
    private $container;

    /** @var AvailableMigration[] */
    private $migrations = [];

    public function __construct(
        MigrationsRepository $migrationRepository,
        ServiceProviderInterface $container
    ) {
        $this->migrationRepository = $migrationRepository;
        $this->container           = $container;
    }

    public function hasMigration(string $version): bool
    {
        return $this->container->has($version) || $this->migrationRepository->hasMigration($version);
    }

    public function getMigration(Version $version): AvailableMigration
    {
        if (! isset($this->migrations[(string) $version]) && ! $this->loadMigrationFromContainer($version)) {
            return $this->migrationRepository->getMigration($version);
        }

        return $this->migrations[(string) $version];
    }

    /**
     * Returns a non-sorted set of migrations.
     */
    public function getMigrations(): AvailableMigrationsSet
    {
        $this->loadMigrationsFromContainer();

        $migrations = $this->migrations;
        foreach ($this->migrationRepository->getMigrations()->getItems() as $availableMigration) {
            $version = $availableMigration->getVersion();

            if (isset($migrations[(string) $version])) {
                throw DuplicateMigrationVersion::new(
                    (string) $version,
                    (string) $version
                );
            }

            $migrations[(string) $version] = $availableMigration;
        }

        return new AvailableMigrationsSet($migrations);
    }

    private function loadMigrationsFromContainer(): void
    {
        foreach ($this->container->getProvidedServices() as $id) {
            if (isset($this->migrations[$id])) {
                continue;
            }

            $this->loadMigrationFromContainer(new Version($id));
        }
    }

    private function loadMigrationFromContainer(Version $version): bool
    {
        if (! $this->container->has((string) $version)) {
            return false;
        }

        $migration = $this->container->get((string) $version);
        assert($migration instanceof AbstractMigration);

        $this->migrations[(string) $version] = new AvailableMigration($version, $migration);

        return true;
    }
}
