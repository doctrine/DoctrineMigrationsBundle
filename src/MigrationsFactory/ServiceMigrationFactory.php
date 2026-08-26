<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsFactory;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Contracts\Service\ServiceProviderInterface;

/** @internal */
final class ServiceMigrationFactory implements MigrationFactory
{
    /** @param ServiceProviderInterface<AbstractMigration> $container */
    public function __construct(private MigrationFactory $migrationFactory, private ServiceProviderInterface $container)
    {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        if ($this->container->has($migrationClassName)) {
            return $this->container->get($migrationClassName);
        }

        return $this->migrationFactory->createVersion($migrationClassName);
    }
}
