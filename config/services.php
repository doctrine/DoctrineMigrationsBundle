<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\Bundle\MigrationsBundle\EventListener\SchemaFilterListener;
use Doctrine\Bundle\MigrationsBundle\MigrationsFactory\ContainerAwareMigrationFactory;
use Doctrine\Bundle\MigrationsBundle\MigrationsRepository\ServiceMigrationsRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ConnectionRegistryConnection;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\EntityManager\ManagerRegistryEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\CurrentCommand;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\ListCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\Migrations\Version\MigrationFactory;
use Psr\Log\LoggerInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('doctrine.migrations.dependency_factory', DependencyFactory::class)
            ->args([
                service('doctrine.migrations.configuration_loader'),
                abstract_arg('loader service'),
                service('logger')->nullOnInvalid(),
            ])

        ->set('doctrine.migrations.configuration_loader', ExistingConfiguration::class)
            ->args([service('doctrine.migrations.configuration')])

        ->set('doctrine.migrations.connection_loader', ExistingConnection::class)

        ->set('doctrine.migrations.em_loader', ExistingEntityManager::class)

        ->set('doctrine.migrations.entity_manager_registry_loader', ManagerRegistryEntityManager::class)
            ->args([service('doctrine')])
            ->factory([ManagerRegistryEntityManager::class, 'withSimpleDefault'])

        ->set('doctrine.migrations.connection_registry_loader', ConnectionRegistryConnection::class)
            ->args([service('doctrine')])
            ->factory([ConnectionRegistryConnection::class, 'withSimpleDefault'])

        ->set('doctrine.migrations.configuration', Configuration::class)

        ->set('doctrine.migrations.migrations_factory', MigrationFactory::class)
            ->factory([service('doctrine.migrations.dependency_factory'), 'getMigrationFactory'])

        ->set('doctrine.migrations.container_aware_migrations_factory', ContainerAwareMigrationFactory::class)
            ->decorate('doctrine.migrations.migrations_factory')
            ->args([
                service('doctrine.migrations.container_aware_migrations_factory.inner'),
                service('service_container'),
            ])

        ->set('doctrine.migrations.service_migrations_repository', ServiceMigrationsRepository::class)
            ->args([
                abstract_arg('migrations locator'),
            ])

        ->set('doctrine.migrations.connection', Connection::class)
            ->factory([service('doctrine.migrations.dependency_factory'), 'getConnection'])

        ->set('doctrine.migrations.logger', LoggerInterface::class)
            ->factory([service('doctrine.migrations.dependency_factory'), 'getLogger'])

        ->set('doctrine_migrations.diff_command', DiffCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:diff',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:diff'])

        ->set('doctrine_migrations.sync_metadata_command', SyncMetadataCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:sync-metadata-storage',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:sync-metadata-storage'])

        ->set('doctrine_migrations.versions_command', ListCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:versions',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:list'])

        ->set('doctrine_migrations.current_command', CurrentCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:current',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:current'])

        ->set('doctrine_migrations.dump_schema_command', DumpSchemaCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:dump-schema',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:dump-schema'])

        ->set('doctrine_migrations.execute_command', ExecuteCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:execute',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:execute'])

        ->set('doctrine_migrations.generate_command', GenerateCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:generate',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:generate'])

        ->set('doctrine_migrations.latest_command', LatestCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:latest',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:latest'])

        ->set('doctrine_migrations.migrate_command', MigrateCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:migrate',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:migrate'])

        ->set('doctrine_migrations.rollup_command', RollupCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:rollup',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:rollup'])

        ->set('doctrine_migrations.status_command', StatusCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:status',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:status'])

        ->set('doctrine_migrations.up_to_date_command', UpToDateCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:up-to-date',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:up-to-date'])

        ->set('doctrine_migrations.version_command', VersionCommand::class)
            ->args([
                service('doctrine.migrations.dependency_factory'),
                'doctrine:migrations:version',
            ])
            ->tag('console.command', ['command' => 'doctrine:migrations:version'])

        ->set('doctrine_migrations.schema_filter_listener', SchemaFilterListener::class)
            // The "doctrine.dbal.schema_filter" tag is dynamically added for each connection
            ->tag('kernel.event_listener', ['event' => 'console.command', 'method' => 'onConsoleCommand']);
};
