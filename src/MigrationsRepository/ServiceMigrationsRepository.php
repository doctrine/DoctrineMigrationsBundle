<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsRepository;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\FilesystemMigrationsRepository;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function array_keys;

/**
 * Loads the migrations registered as services and delegates to the default repository of
 * doctrine/migrations for the configured migration classes and directories, so that the
 * migrations which are not registered as services (e.g. the ones shipped by third party bundles)
 * are available as well.
 *
 * @internal
 */
final class ServiceMigrationsRepository implements MigrationsRepository
{
    /** @var ServiceProviderInterface<AbstractMigration> */
    private $container;

    /** @var Configuration|null */
    private $configuration;

    /** @var MigrationFinder|null */
    private $migrationFinder;

    /** @var MigrationFactory|null */
    private $migrationFactory;

    /** @var MigrationsRepository|null */
    private $configuredMigrationsRepository;

    /** @var array<string, AvailableMigration> */
    private $migrations = [];

    /** @param ServiceProviderInterface<AbstractMigration> $container */
    public function __construct(
        ServiceProviderInterface $container,
        ?Configuration $configuration = null,
        ?MigrationFinder $migrationFinder = null,
        ?MigrationFactory $migrationFactory = null
    ) {
        $this->container        = $container;
        $this->configuration    = $configuration;
        $this->migrationFinder  = $migrationFinder;
        $this->migrationFactory = $migrationFactory;
    }

    public function hasMigration(string $version): bool
    {
        if (isset($this->migrations[$version]) || $this->container->has($version)) {
            return true;
        }

        $repository = $this->getConfiguredMigrationsRepository();

        return $repository !== null && $repository->hasMigration($version);
    }

    public function getMigration(Version $version): AvailableMigration
    {
        $migration = $this->loadMigrationFromContainer($version);

        if ($migration !== null) {
            return $migration;
        }

        $repository = $this->getConfiguredMigrationsRepository();

        if ($repository === null) {
            throw MigrationClassNotFound::new((string) $version);
        }

        return $repository->getMigration($version);
    }

    /**
     * Returns a non-sorted set of migrations.
     */
    public function getMigrations(): AvailableMigrationsSet
    {
        foreach (array_keys($this->container->getProvidedServices()) as $id) {
            $this->loadMigrationFromContainer(new Version($id));
        }

        $migrations = $this->migrations;
        $repository = $this->getConfiguredMigrationsRepository();

        if ($repository !== null) {
            foreach ($repository->getMigrations()->getItems() as $migration) {
                $migrations[(string) $migration->getVersion()] = $migrations[(string) $migration->getVersion()] ?? $migration;
            }
        }

        return new AvailableMigrationsSet($migrations);
    }

    private function loadMigrationFromContainer(Version $version): ?AvailableMigration
    {
        $id = (string) $version;

        if (isset($this->migrations[$id])) {
            return $this->migrations[$id];
        }

        if (! $this->container->has($id)) {
            return null;
        }

        return $this->migrations[$id] = new AvailableMigration($version, $this->container->get($id));
    }

    /**
     * The repository of doctrine/migrations for the configured migration classes and directories,
     * resolving the migrations registered as services from the container instead of instantiating
     * them a second time.
     */
    private function getConfiguredMigrationsRepository(): ?MigrationsRepository
    {
        if ($this->configuredMigrationsRepository !== null) {
            return $this->configuredMigrationsRepository;
        }

        if ($this->configuration === null || $this->migrationFinder === null || $this->migrationFactory === null) {
            return null;
        }

        return $this->configuredMigrationsRepository = new FilesystemMigrationsRepository(
            $this->configuration->getMigrationClasses(),
            $this->configuration->getMigrationDirectories(),
            $this->migrationFinder,
            new ServiceMigrationFactory($this->container, $this->migrationFactory)
        );
    }
}
