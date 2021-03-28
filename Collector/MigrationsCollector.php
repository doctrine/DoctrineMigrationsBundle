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

        $executedMigrations  = $metadataStorage->getExecutedMigrations();
        $availableMigrations = $planCalculator->getMigrations();

        $this->data['available_migrations'] = $this->flattenAvailableMigrations($availableMigrations, $executedMigrations);
        $this->data['executed_migrations'] = $this->flattenExecutedMigrations($executedMigrations, $availableMigrations);

        $this->data['new_migrations'] = $this->flattenAvailableMigrations($statusCalculator->getNewMigrations());
        $this->data['unavailable_migrations'] = $this->flattenExecutedMigrations($statusCalculator->getExecutedUnavailableMigrations());

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
        return 'doctrine_migrations';
    }

    public function getData()
    {
        return $this->data;
    }

    public function reset()
    {
        $this->data = [];
    }

    private function flattenAvailableMigrations(AvailableMigrationsList $migrationsList, ?ExecutedMigrationsList $executedMigrations = null): array
    {
        return array_map(static function (AvailableMigration $migration) use ($executedMigrations) {
            $executedMigration = $executedMigrations && $executedMigrations->hasMigration($migration->getVersion())
                ? $executedMigrations->getMigration($migration->getVersion())
                : null;

            return [
                'version' => (string)$migration->getVersion(),
                'is_new' => !$executedMigration,
                'is_unavailable' => false,
                'description' => $migration->getMigration()->getDescription(),
                'executed_at' =>  $executedMigration ? $executedMigration->getExecutedAt() : null,
                'execution_time' =>  $executedMigration ? $executedMigration->getExecutionTime() : null,
                'file' => (new \ReflectionClass($migration->getMigration()))->getFileName(),
            ];
        }, $migrationsList->getItems());
    }

    private function flattenExecutedMigrations(ExecutedMigrationsList $migrationsList, ?AvailableMigrationsList $availableMigrations = null): array
    {
        return array_map(static function (ExecutedMigration $migration) use ($availableMigrations) {

            $availableMigration = $availableMigrations && $availableMigrations->hasMigration($migration->getVersion())
                ? $availableMigrations->getMigration($migration->getVersion())->getMigration()
                : null;

            return [
                'version' => (string)$migration->getVersion(),
                'is_new' => false,
                'is_unavailable' => !$availableMigration,
                'description' => $availableMigration ? $availableMigration->getDescription() : null,
                'executed_at' => $migration->getExecutedAt(),
                'execution_time' => $migration->getExecutionTime(),
                'file' => $availableMigration ? (new \ReflectionClass($availableMigration))->getFileName() : null,
            ];
        }, $migrationsList->getItems());
    }
}
