<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsRepository;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Returns the migrations registered as services from the container, and lets the decorated
 * factory instantiate the other ones (e.g. the migrations shipped by third party bundles).
 *
 * @internal
 */
final class ServiceMigrationFactory implements MigrationFactory
{
    /** @var ServiceProviderInterface<AbstractMigration> */
    private $container;

    /** @var MigrationFactory */
    private $migrationFactory;

    /** @param ServiceProviderInterface<AbstractMigration> $container */
    public function __construct(ServiceProviderInterface $container, MigrationFactory $migrationFactory)
    {
        $this->container        = $container;
        $this->migrationFactory = $migrationFactory;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        if ($this->container->has($migrationClassName)) {
            return $this->container->get($migrationClassName);
        }

        return $this->migrationFactory->createVersion($migrationClassName);
    }
}
