<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Collector;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
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

        $this->data['available_migrations'] = $this->flattenMigrations($planCalculator->getMigrations());
        $this->data['executed_migrations'] = $this->flattenMigrations($metadataStorage->getExecutedMigrations());
        $this->data['new_migrations'] = $this->flattenMigrations($statusCalculator->getNewMigrations());
        $this->data['unavailable_migrations'] = $this->flattenMigrations($statusCalculator->getExecutedUnavailableMigrations());

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

    /**
     * @param AvailableMigrationsList|ExecutedMigrationsList $migrationsList
     */
    private function flattenMigrations($migrationsList): array
    {
        return array_map(static function ($migration) {
            return (string)$migration->getVersion();
        }, $migrationsList->getItems());
    }
}
