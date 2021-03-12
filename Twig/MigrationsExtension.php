<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Twig;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Version;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MigrationsExtension extends AbstractExtension
{
    private $dependencyFactory;
    private $executedMigrations;
    private $availableMigrations;

    public function __construct(DependencyFactory $dependecyFactory)
    {
        $this->dependencyFactory = $dependecyFactory;
    }

    /**
     * @uses \Doctrine\Bundle\MigrationsBundle\Twig\MigrationsExtension::getMigrationInfo()
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('migration_info', [$this, 'getMigrationInfo']),
        ];
    }

    public function getMigrationInfo(string $name): array
    {
        $version = new Version($name);

        if (null === $this->executedMigrations) {
            $this->executedMigrations  = $this->dependencyFactory->getMetadataStorage()->getExecutedMigrations();
            $this->availableMigrations = $this->dependencyFactory->getMigrationPlanCalculator()->getMigrations();
        }

        $executedMigration = $this->executedMigrations->hasMigration($version)
            ? $this->executedMigrations->getMigration($version) : null;
        $availableMigration = $this->availableMigrations->hasMigration($version)
            ? $this->availableMigrations->getMigration($version) : null;

        return [
            'description' => $availableMigration ? $availableMigration->getMigration()->getDescription() : '',
            'executed_at' => $executedMigration ? $executedMigration->getExecutedAt() : null,
            'execution_time' => $executedMigration ? $executedMigration->getExecutionTime() : null,
            'file' => $availableMigration ? (new \ReflectionClass($availableMigration->getMigration()))->getFileName() : null,
        ];
    }
}
