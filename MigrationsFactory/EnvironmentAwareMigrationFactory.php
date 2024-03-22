<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\MigrationsFactory;

use Doctrine\Bundle\MigrationsBundle\Migration\EnvironmentAwareMigrationInterface;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;

class EnvironmentAwareMigrationFactory implements MigrationFactory
{
    /**
     * @var string
     */
    private $environment;

    /**
     * @var MigrationFactory
     */
    private $migrationFactory;

    public function __construct(MigrationFactory $migrationFactory, string $environment)
    {
        $this->environment = $environment;
        $this->migrationFactory = $migrationFactory;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $migration = $this->migrationFactory->createVersion($migrationClassName);

        if ($migration instanceof EnvironmentAwareMigrationInterface) {
            $migration->setEnvironment($this->environment);
        }

        return $migration;
    }
}
