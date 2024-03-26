<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationFinder;

use Doctrine\Migrations\Finder\MigrationFinder;
use Psr\Container\ContainerInterface;

use function array_values;

final class FilterServiceMigrationFinder implements MigrationFinder
{
    /** @var MigrationFinder */
    private $migrationFinder;

    /** @var ContainerInterface */
    private $container;

    public function __construct(MigrationFinder $migrationFinder, ContainerInterface $container)
    {
        $this->migrationFinder = $migrationFinder;
        $this->container       = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function findMigrations(string $directory, ?string $namespace = null): array
    {
        $migrations = $this->migrationFinder->findMigrations(
            $directory,
            $namespace
        );

        foreach ($migrations as $i => $migration) {
            if (! $this->container->has($migration)) {
                continue;
            }

            unset($migrations[$i]);
        }

        return array_values($migrations);
    }
}
