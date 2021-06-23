<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Collector;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;

class MigrationsFlattener
{
    public function flattenAvailableMigrations(AvailableMigrationsList $migrationsList): array
    {
        return array_map(static function (AvailableMigration $migration) {
            return [
                'version' => (string)$migration->getVersion(),
                'is_new' => true,
                'is_unavailable' => false,
                'description' => $migration->getMigration()->getDescription(),
                'executed_at' =>  null,
                'execution_time' =>  null,
                'file' => (new \ReflectionClass($migration->getMigration()))->getFileName(),
            ];
        }, $migrationsList->getItems());
    }

    public function flattenExecutedMigrations(ExecutedMigrationsList $migrationsList, AvailableMigrationsList $availableMigrations): array
    {
        return array_map(static function (ExecutedMigration $migration) use ($availableMigrations) {

            $availableMigration = $availableMigrations->hasMigration($migration->getVersion())
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
