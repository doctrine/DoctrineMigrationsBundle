<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Collector;

use Doctrine\DBAL\Exception;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;
use Throwable;

use function count;
use function get_class;

/** @internal */
final class MigrationsCollector extends DataCollector
{
    public function __construct(
        private readonly DependencyFactory $dependencyFactory,
        private readonly MigrationsFlattener $flattener,
    ) {
    }

    public function collect(Request $request, Response $response, Throwable|null $exception = null): void
    {
        if ($this->data !== []) {
            return;
        }

        $metadataStorage = $this->dependencyFactory->getMetadataStorage();
        $planCalculator  = $this->dependencyFactory->getMigrationPlanCalculator();

        try {
            $executedMigrations = $metadataStorage->getExecutedMigrations();
        } catch (Exception $dbalException) {
            $this->dependencyFactory->getLogger()->error(
                'error while trying to collect executed migrations',
                ['exception' => $dbalException],
            );

            return;
        }

        $availableMigrations = $planCalculator->getMigrations();

        $this->data['available_migrations_count']   = count($availableMigrations);
        $unavailableMigrations                      = $executedMigrations->unavailableSubset($availableMigrations);
        $this->data['unavailable_migrations_count'] = count($unavailableMigrations);

        $newMigrations                     = $availableMigrations->newSubset($executedMigrations);
        $this->data['new_migrations']      = $this->flattener->flattenAvailableMigrations($newMigrations);
        $this->data['executed_migrations'] = $this->flattener->flattenExecutedMigrations($executedMigrations, $availableMigrations);

        $this->data['storage'] = $metadataStorage::class;
        $configuration         = $this->dependencyFactory->getConfiguration();
        $storage               = $configuration->getMetadataStorageConfiguration();
        if ($storage instanceof TableMetadataStorageConfiguration) {
            $this->data['table']  = $storage->getTableName();
            $this->data['column'] = $storage->getVersionColumnName();
        }

        $connection           = $this->dependencyFactory->getConnection();
        $this->data['driver'] = get_class($connection->getDriver());
        $this->data['name']   = $connection->getDatabase();

        $this->data['namespaces'] = $configuration->getMigrationDirectories();
    }

    public function getName(): string
    {
        return 'doctrine_migrations';
    }

    /** @return array<string, mixed>|Data */
    public function getData(): array|Data
    {
        return $this->data;
    }

    public function reset(): void
    {
        $this->data = [];
    }
}
