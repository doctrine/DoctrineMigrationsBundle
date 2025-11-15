<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsRepository;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\Version;
use Symfony\Contracts\Service\ServiceProviderInterface;

/** @internal */
final class ServiceMigrationsRepository implements MigrationsRepository
{
    /** @var ServiceProviderInterface<AbstractMigration> */
    private $container;

    /** @var array<string, AvailableMigration> */
    private $migrations = [];

    /** @param ServiceProviderInterface<AbstractMigration> $container */
    public function __construct(ServiceProviderInterface $container)
    {
        $this->container = $container;
    }

    public function hasMigration(string $version): bool
    {
        return isset($this->migrations[$version]) || $this->container->has($version);
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
        foreach ($this->container->getProvidedServices() as $id) {
            $this->loadMigrationFromContainer(new Version($id));
        }

        return new AvailableMigrationsSet($this->migrations);
    }

    private function loadMigrationFromContainer(Version $version): void
    {
        $id = (string) $version;

        if (isset($this->migrations[$id])) {
            return;
        }

        if (! $this->container->has($id)) {
            throw MigrationClassNotFound::new($id);
        }

        $this->migrations[$id] = new AvailableMigration($version, $this->container->get($id));
    }
}
