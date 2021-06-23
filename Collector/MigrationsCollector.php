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
    /** @var MigrationsFlattener */
    private $flattener;

    public function __construct(DependencyFactory $dependencyFactory, MigrationsFlattener $migrationsFlattener)
    {
        $this->dependencyFactory = $dependencyFactory;
        $this->flattener = $migrationsFlattener;
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $metadataStorage = $this->dependencyFactory->getMetadataStorage();
        $planCalculator = $this->dependencyFactory->getMigrationPlanCalculator();

        $executedMigrations  = $metadataStorage->getExecutedMigrations();
        $availableMigrations = $planCalculator->getMigrations();

        $this->data['available_migrations_count'] = count($availableMigrations);
        $unavailableMigrations = $this->filterUnavailableMigrations($executedMigrations, $availableMigrations);
        $this->data['unavailable_migrations_count'] = count($unavailableMigrations);

        $newMigrations = $this->filterNewMigrations($availableMigrations, $executedMigrations);
        $this->data['new_migrations'] = $this->flattener->flattenAvailableMigrations($newMigrations);
        $this->data['executed_migrations'] = $this->flattener->flattenExecutedMigrations($executedMigrations, $availableMigrations);

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

    private function filterUnavailableMigrations(
        ExecutedMigrationsList $executedMigrations,
        AvailableMigrationsList $availableMigrations
    ): ExecutedMigrationsList {
        $unavailableMigrations = array_filter($executedMigrations->getItems(), static function (ExecutedMigration $migration) use ($availableMigrations) {
            return !$availableMigrations->hasMigration($migration->getVersion());
        });

        return new ExecutedMigrationsList($unavailableMigrations);
    }

    private function filterNewMigrations(
        AvailableMigrationsList $availableMigrations,
        ExecutedMigrationsList $executedMigrations
    ): AvailableMigrationsList {
        $newMigrations = array_filter($availableMigrations->getItems(), static function (AvailableMigration $migration) use ($executedMigrations) {
            return !$executedMigrations->hasMigration($migration->getVersion());
        });

        return new AvailableMigrationsList($newMigrations);
    }
}
