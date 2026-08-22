<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsRepository;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function array_keys;
use function array_merge;
use function class_exists;

/**
 * Loads the migrations registered as services and falls back to the configured migration classes and
 * directories for the other ones (e.g. the migrations shipped by third party bundles).
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

    /** @var array<string, AvailableMigration> */
    private $migrations = [];

    /** @var bool */
    private $filesystemMigrationsLoaded = false;

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

        $this->loadMigrationsFromFilesystem();

        return isset($this->migrations[$version]);
    }

    public function getMigration(Version $version): AvailableMigration
    {
        $this->loadMigrationFromContainer($version);

        return $this->migrations[(string) $version];
    }

    /**
     * Returns a non-sorted set of migrations.
     */
    public function getMigrations(): AvailableMigrationsSet
    {
        foreach (array_keys($this->container->getProvidedServices()) as $id) {
            $this->loadMigrationFromContainer(new Version($id));
        }

        $this->loadMigrationsFromFilesystem();

        return new AvailableMigrationsSet($this->migrations);
    }

    private function loadMigrationFromContainer(Version $version): void
    {
        $id = (string) $version;

        if (isset($this->migrations[$id])) {
            return;
        }

        if (! $this->container->has($id)) {
            $this->loadMigrationsFromFilesystem();

            if (isset($this->migrations[$id])) {
                return;
            }

            throw MigrationClassNotFound::new($id);
        }

        $this->migrations[$id] = new AvailableMigration($version, $this->container->get($id));
    }

    /**
     * Registers the migrations found in the configured classes and directories which are not
     * registered as services, the way the default repository of doctrine/migrations does.
     */
    private function loadMigrationsFromFilesystem(): void
    {
        if ($this->filesystemMigrationsLoaded) {
            return;
        }

        $this->filesystemMigrationsLoaded = true;

        if ($this->configuration === null || $this->migrationFinder === null || $this->migrationFactory === null) {
            return;
        }

        $classes = $this->configuration->getMigrationClasses();

        foreach ($this->configuration->getMigrationDirectories() as $namespace => $path) {
            $classes = array_merge($classes, $this->migrationFinder->findMigrations($path, $namespace));
        }

        foreach ($classes as $class) {
            if (isset($this->migrations[$class]) || $this->container->has($class)) {
                continue;
            }

            if (! class_exists($class)) {
                throw MigrationClassNotFound::new($class);
            }

            $this->migrations[$class] = new AvailableMigration(
                new Version($class),
                $this->migrationFactory->createVersion($class)
            );
        }
    }
}
