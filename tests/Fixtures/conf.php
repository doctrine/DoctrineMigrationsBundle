<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine_migrations', [
        'all_or_nothing' => true,
        'check_database_platform' => true,
        'organize_migrations' => 'BY_YEAR_AND_MONTH',
        'migrations_paths' => [
            'DoctrineMigrationsTest' => 'a',
            'DoctrineMigrationsTest2' => 'b',
        ],
        'migrations' => [
            'Foo',
            'Bar',
        ],
        'storage' => [
            'table_storage' => [
                'table_name' => 'doctrine_migration_versions_test',
                'version_column_name' => 'doctrine_migration_column_test',
                'version_column_length' => 2000,
                'execution_time_column_name' => 'doctrine_migration_execution_time_column_test',
                'executed_at_column_name' => 'doctrine_migration_executed_at_column_test',
            ],
        ],
    ]);
};
