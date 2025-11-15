<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection;

use Doctrine\Bundle\MigrationsBundle\Collector\MigrationsCollector;
use Doctrine\Bundle\MigrationsBundle\Collector\MigrationsFlattener;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\MigrationFactory;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;
use function assert;
use function explode;
use function implode;
use function interface_exists;
use function is_array;
use function sprintf;
use function strlen;
use function substr;

/** @final */
class DoctrineMigrationsExtension extends Extension
{
    /**
     * Responds to the migrations configuration parameter.
     *
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $locator = new FileLocator(__DIR__ . '/../../config/');
        $loader  = new PhpFileLoader($container, $locator);

        $loader->load('services.php');

        if ($config['enable_service_migrations']) {
            $container->registerForAutoconfiguration(AbstractMigration::class)
                ->addTag('doctrine_migrations.migration');

            if (! isset($config['services'][MigrationsRepository::class])) {
                $config['services'][MigrationsRepository::class] = 'doctrine.migrations.service_migrations_repository';
            }
        } else {
            $container->removeDefinition('doctrine.migrations.service_migrations_repository');
            $container->removeDefinition('doctrine.migrations.connection');
            $container->removeDefinition('doctrine.migrations.logger');
        }

        $configurationDefinition = $container->getDefinition('doctrine.migrations.configuration');

        foreach ($config['migrations_paths'] as $ns => $path) {
            $path = $this->checkIfBundleRelativePath($path, $container);
            $configurationDefinition->addMethodCall('addMigrationsDirectory', [$ns, $path]);
        }

        foreach ($config['migrations'] as $migrationClass) {
            $configurationDefinition->addMethodCall('addMigrationClass', [$migrationClass]);
        }

        if ($config['organize_migrations'] !== false) {
            $configurationDefinition->addMethodCall('setMigrationOrganization', [$config['organize_migrations']]);
        }

        if ($config['custom_template'] !== null) {
            $configurationDefinition->addMethodCall('setCustomTemplate', [$config['custom_template']]);
        }

        $configurationDefinition->addMethodCall('setAllOrNothing', [$config['all_or_nothing']]);
        $configurationDefinition->addMethodCall('setCheckDatabasePlatform', [$config['check_database_platform']]);

        if ($config['enable_profiler']) {
            $this->registerCollector($container);
        }

        $configurationDefinition->addMethodCall('setTransactional', [$config['transactional']]);

        $diDefinition = $container->getDefinition('doctrine.migrations.dependency_factory');

        if (! isset($config['services'][MigrationFactory::class])) {
            $config['services'][MigrationFactory::class] = 'doctrine.migrations.migrations_factory';
        }

        foreach ($config['services'] as $doctrineId => $symfonyId) {
            $diDefinition->addMethodCall('setDefinition', [$doctrineId, new ServiceClosureArgument(new Reference($symfonyId))]);
        }

        foreach ($config['factories'] as $doctrineId => $symfonyId) {
            $diDefinition->addMethodCall('setDefinition', [$doctrineId, new Reference($symfonyId)]);
        }

        if (isset($config['services'][MetadataStorage::class])) {
            $container->removeDefinition('doctrine_migrations.schema_filter_listener');
        } else {
            $filterDefinition     = $container->getDefinition('doctrine_migrations.schema_filter_listener');
            $storageConfiguration = $config['storage']['table_storage'];

            $storageDefinition = new Definition(TableMetadataStorageConfiguration::class);
            $container->setDefinition('doctrine.migrations.storage.table_storage', $storageDefinition);
            $container->setAlias('doctrine.migrations.metadata_storage', 'doctrine.migrations.storage.table_storage');

            if ($storageConfiguration['table_name'] === null) {
                $filterDefinition->addArgument('doctrine_migration_versions');
            } else {
                $storageDefinition->addMethodCall('setTableName', [$storageConfiguration['table_name']]);
                $filterDefinition->addArgument($storageConfiguration['table_name']);
            }

            if ($storageConfiguration['version_column_name'] !== null) {
                $storageDefinition->addMethodCall('setVersionColumnName', [$storageConfiguration['version_column_name']]);
            }

            if ($storageConfiguration['version_column_length'] !== null) {
                $storageDefinition->addMethodCall('setVersionColumnLength', [$storageConfiguration['version_column_length']]);
            }

            if ($storageConfiguration['executed_at_column_name'] !== null) {
                $storageDefinition->addMethodCall('setExecutedAtColumnName', [$storageConfiguration['executed_at_column_name']]);
            }

            if ($storageConfiguration['execution_time_column_name'] !== null) {
                $storageDefinition->addMethodCall('setExecutionTimeColumnName', [$storageConfiguration['execution_time_column_name']]);
            }

            $configurationDefinition->addMethodCall('setMetadataStorageConfiguration', [new Reference('doctrine.migrations.storage.table_storage')]);

            // Add tag to the filter for each Doctrine connection, so the table is ignored for multiple connections
            if ($container->hasParameter('doctrine.connections')) {
                /** @var array<string, string> $connections */
                $connections = $container->getParameter('doctrine.connections');
                foreach (array_keys($connections) as $connection) {
                    $filterDefinition->addTag('doctrine.dbal.schema_filter', ['connection' => $connection]);
                }
            }
        }

        if ($config['em'] !== null && $config['connection'] !== null) {
            throw new InvalidArgumentException(
                'You cannot specify both "connection" and "em" in the DoctrineMigrationsBundle configurations.'
            );
        }

        $container->setParameter('doctrine.migrations.preferred_em', $config['em']);
        $container->setParameter('doctrine.migrations.preferred_connection', $config['connection']);

        if (interface_exists(ContainerAwareInterface::class)) {
            return;
        }

        $container->removeDefinition('doctrine.migrations.container_aware_migrations_factory');
    }

    private function checkIfBundleRelativePath(string $path, ContainerBuilder $container): string
    {
        if (isset($path[0]) && $path[0] === '@') {
            $pathParts  = explode('/', $path);
            $bundleName = substr($pathParts[0], 1);

            $bundlePath = $this->getBundlePath($bundleName, $container);

            return $bundlePath . substr($path, strlen('@' . $bundleName));
        }

        return $path;
    }

    private function getBundlePath(string $bundleName, ContainerBuilder $container): string
    {
        $bundleMetadata = $container->getParameter('kernel.bundles_metadata');
        assert(is_array($bundleMetadata));

        if (! isset($bundleMetadata[$bundleName])) {
            throw new RuntimeException(sprintf(
                'The bundle "%s" has not been registered, available bundles: %s',
                $bundleName,
                implode(', ', array_keys($bundleMetadata))
            ));
        }

        return $bundleMetadata[$bundleName]['path'];
    }

    private function registerCollector(ContainerBuilder $container): void
    {
        $flattenerDefinition = new Definition(MigrationsFlattener::class);
        $container->setDefinition('doctrine_migrations.migrations_flattener', $flattenerDefinition);

        $collectorDefinition = new Definition(MigrationsCollector::class, [
            new Reference('doctrine.migrations.dependency_factory'),
            new Reference('doctrine_migrations.migrations_flattener'),
        ]);
        $collectorDefinition
            ->addTag('data_collector', [
                'template' => '@DoctrineMigrations/Collector/migrations.html.twig',
                'id' => 'doctrine_migrations',
                'priority' => '249',
            ]);
        $container->setDefinition('doctrine_migrations.migrations_collector', $collectorDefinition);
    }

    public function getXsdValidationBasePath(): string
    {
        return __DIR__ . '/../../config/schema';
    }

    public function getNamespace(): string
    {
        return 'http://symfony.com/schema/dic/doctrine/migrations/3.0';
    }
}
