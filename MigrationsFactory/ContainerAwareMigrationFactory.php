<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsFactory;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
            $migration->setContainer($this->container);
        }

        return $migration;
    }
}
