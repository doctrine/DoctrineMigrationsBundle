<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Collector;

use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;

class MigrationsFlattener
{
    public function flattenAvailableMigrations(AvailableMigrationsList $migrationsList, ?ExecutedMigrationsList $executedMigrations = null): array
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

    public function flattenExecutedMigrations(ExecutedMigrationsList $migrationsList, ?AvailableMigrationsList $availableMigrations = null): array
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
