<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Collector;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class MigrationsCollector extends DataCollector
{
    /** @var DependencyFactory */
    private $dependencyFactory;

    public function __construct(DependencyFactory $dependencyFactory)
    {
        $this->dependencyFactory = $dependencyFactory;
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $metadataStorage = $this->dependencyFactory->getMetadataStorage();
        $planCalculator = $this->dependencyFactory->getMigrationPlanCalculator();
        $statusCalculator = $this->dependencyFactory->getMigrationStatusCalculator();

        $availableMigrations = $planCalculator->getMigrations();
        $this->data['available_migrations'] = $this->flattenAvailableMigrations($availableMigrations);
        $this->data['executed_migrations'] = $this->flattenExecutedMigrations(
            $metadataStorage->getExecutedMigrations(),
            $availableMigrations
        );
        $this->data['new_migrations'] = $this->flattenAvailableMigrations($statusCalculator->getNewMigrations());
        $this->data['executed_unavailable_migrations'] = $this->flattenExecutedMigrations(
            $statusCalculator->getExecutedUnavailableMigrations(),
            new AvailableMigrationsList([])
        );

        $this->data['storage'] = get_class($metadataStorage);
        $configuration = $this->dependencyFactory->getConfiguration();
        $storage = $configuration->getMetadataStorageConfiguration();
        if ($storage instanceof TableMetadataStorageConfiguration) {
            $this->data['table'] = $storage->getTableName();
            $this->data['column'] = $storage->getVersionColumnName();
        }

        $connection = $this->dependencyFactory->getConnection();
        $this->data['driver'] = get_class($connection->getDriver());
        $this->data['name'] = $connection->getDatabase();

        $this->data['namespaces'] = $configuration->getMigrationDirectories();
    }

    public function getName()
    {
        return 'migrations';
    }

    public function getData()
    {
        return $this->data;
    }

    public function reset()
    {
        $this->data = [];
    }

    private function flattenExecutedMigrations(
        ExecutedMigrationsList $executedMigrations,
        AvailableMigrationsList $availableMigrations
    ): array {
        return array_map(static function (ExecutedMigration $migration) use ($availableMigrations) {
            $version = $migration->getVersion();
            return [
                'version' => (string)$version,
                'executed_at' => $migration->getExecutedAt(),
                'execution_time' => $migration->getExecutionTime(),
                'description' => $availableMigrations->hasMigration($version)
                    ? $availableMigrations->getMigration($version)->getMigration()->getDescription() : null,
            ];
        }, $executedMigrations->getItems());
    }

    private function flattenAvailableMigrations(AvailableMigrationsList $migrationsList): array
    {
        return array_map(static function (AvailableMigration $migration) {
            return [
                'version' => (string)$migration->getVersion(),
                'description' => $migration->getMigration()->getDescription(),
            ];
        }, $migrationsList->getItems());
    }
}
