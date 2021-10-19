<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Collector;

use Doctrine\DBAL\Exception;
use Doctrine\Migrations\DependencyFactory;
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
        if (!empty($this->data)) {
            return;
        }

        $metadataStorage = $this->dependencyFactory->getMetadataStorage();
        $planCalculator = $this->dependencyFactory->getMigrationPlanCalculator();

        try {
            $executedMigrations = $metadataStorage->getExecutedMigrations();
        } catch (Exception $dbalException) {
            $this->dependencyFactory->getLogger()->error(
                'error while trying to collect executed migrations',
                ['exception' => $dbalException]
            );

            return;
        }

        $availableMigrations = $planCalculator->getMigrations();

        $this->data['available_migrations_count'] = count($availableMigrations);
        $unavailableMigrations = $executedMigrations->unavailableSubset($availableMigrations);
        $this->data['unavailable_migrations_count'] = count($unavailableMigrations);

        $newMigrations = $availableMigrations->newSubset($executedMigrations);
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

    /**
     * @return string
     */
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
}
