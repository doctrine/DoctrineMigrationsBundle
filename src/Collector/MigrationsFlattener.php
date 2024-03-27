<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Collector;

use DateTimeImmutable;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use ReflectionClass;

use function array_map;

class MigrationsFlattener
{
    /**
     * @return array{
     *    version: string,
     *    is_new: true,
     *    is_unavailable: bool,
     *    description: string,
     *    executed_at: null,
     *    execution_time: null,
     *    file: string|false,
     * }[]
     */
    public function flattenAvailableMigrations(AvailableMigrationsList $migrationsList): array
    {
        return array_map(static function (AvailableMigration $migration) {
            return [
                'version' => (string) $migration->getVersion(),
                'is_new' => true,
                'is_unavailable' => false,
                'description' => $migration->getMigration()->getDescription(),
                'executed_at' =>  null,
                'execution_time' =>  null,
                'file' => (new ReflectionClass($migration->getMigration()))->getFileName(),
            ];
        }, $migrationsList->getItems());
    }

    /**
     * @return array{
     *    version: string,
     *    is_new: false,
     *    is_unavailable: bool,
     *    description: string|null,
     *    executed_at: DateTimeImmutable|null,
     *    execution_time: float|null,
     *    file: string|false|null,
     * }[]
     */
    public function flattenExecutedMigrations(ExecutedMigrationsList $migrationsList, AvailableMigrationsList $availableMigrations): array
    {
        return array_map(static function (ExecutedMigration $migration) use ($availableMigrations) {
            $availableMigration = $availableMigrations->hasMigration($migration->getVersion())
                ? $availableMigrations->getMigration($migration->getVersion())->getMigration()
                : null;

            return [
                'version' => (string) $migration->getVersion(),
                'is_new' => false,
                'is_unavailable' => $availableMigration === null,
                'description' => $availableMigration !== null ? $availableMigration->getDescription() : null,
                'executed_at' => $migration->getExecutedAt(),
                'execution_time' => $migration->getExecutionTime(),
                'file' => $availableMigration !== null ? (new ReflectionClass($availableMigration))->getFileName() : null,
            ];
        }, $migrationsList->getItems());
    }
}
