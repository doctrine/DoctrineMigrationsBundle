<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsFactory;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @deprecated This class is not compatible with Symfony >= 7
 */
class ContainerAwareMigrationFactory implements MigrationFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var MigrationFactory
     */
    private $migrationFactory;

    public function __construct(MigrationFactory $migrationFactory, ContainerInterface $container)
    {
        $this->container = $container;
        $this->migrationFactory = $migrationFactory;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $migration = $this->migrationFactory->createVersion($migrationClassName);

        if ($migration instanceof ContainerAwareInterface) {
            trigger_deprecation('doctrine/doctrine-migrations-bundle', '3.3', 'Migration "%s" implements "%s" to gain access to the application\'s service container. This method is deprecated and won\'t work with Symfony 7.', $migrationClassName, ContainerAwareInterface::class);

            $migration->setContainer($this->container);
        }

        return $migration;
    }
}
