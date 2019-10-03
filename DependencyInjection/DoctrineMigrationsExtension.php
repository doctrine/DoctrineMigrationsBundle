<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use function sprintf;

/**
 * DoctrineMigrationsExtension.
 */
class DoctrineMigrationsExtension extends Extension
{
    /**
     * Responds to the migrations configuration parameter.
     *
     * @param string[][] $configs
     */
    public function load(array $configs, ContainerBuilder $container) : void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $locator = new FileLocator(__DIR__ . '/../Resources/config/');
        $loader  = new XmlFileLoader($container, $locator);

        $loader->load('services.xml');

        $configurationDefinition = $container->getDefinition('doctrine.migrations.configuration');

        $configurationDefinition->addMethodCall('setName', [$config['name']]);

        foreach ($config['migrations_paths'] as $ns => $path) {
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

        $diDefinition = $container->getDefinition('doctrine.migrations.dependency_factory');

        foreach ($config['services'] as $doctrineId => $symfonyId) {
            $diDefinition->addMethodCall('setService', [$doctrineId, new Reference($symfonyId)]);
        }

        if (! isset($config['services'][MetadataStorage::class])) {
            $storageConfiguration = $config['storage']['table_storage'];

            $storageDefinition = new Definition(TableMetadataStorageConfiguration::class);
            $container->setDefinition('doctrine.migrations.storage.table_storage', $storageDefinition);
            $container->setAlias('doctrine.migrations.metadata_storage', 'doctrine.migrations.storage.table_storage');

            if ($storageConfiguration['table_name']!== null) {
                $storageDefinition->addMethodCall('setTableName', [$storageConfiguration['table_name']]);
            }
            if ($storageConfiguration['version_column_name']!== null) {
                $storageDefinition->addMethodCall('setVersionColumnName', [$storageConfiguration['version_column_name']]);
            }
            if ($storageConfiguration['version_column_length']!== null) {
                $storageDefinition->addMethodCall('setVersionColumnLength', [$storageConfiguration['version_column_length']]);
            }
            if ($storageConfiguration['executed_at_column_name']!== null) {
                $storageDefinition->addMethodCall('setExecutedAtColumnName', [$storageConfiguration['executed_at_column_name']]);
            }
            if ($storageConfiguration['execution_time_column_name']!== null) {
                $storageDefinition->addMethodCall('setExecutionTimeColumnName', [$storageConfiguration['execution_time_column_name']]);
            }

            $configurationDefinition->addMethodCall('setMetadataStorageConfiguration', [new Reference('doctrine.migrations.storage.table_storage')]);
        }

        if ($config['em'] !== null && $config['connection'] !== null) {
            throw new InvalidArgumentException(
                'You cannot specify both "connection" and "em" in the DoctrineMigrationsBundle configurations.'
            );
        }

        $emID = sprintf('doctrine.orm.%s_entity_manager', $config['em'] ?: 'default');

        if ($container->has($emID)) {
            $container->getDefinition('doctrine.migrations.em_loader')
                ->setArgument(0, new Reference($emID));

            $diDefinition->setFactory([DependencyFactory::class, 'fromEntityManager']);
            $diDefinition->setArgument(1, new Reference('doctrine.migrations.em_loader'));
        } else {
            $connectionId = sprintf('doctrine.dbal.%s_connection', $config['connection'] ?? 'default');
            $container->getDefinition('doctrine.migrations.connection_loader')
                ->setArgument(0, new Reference($connectionId));

            $diDefinition->setFactory([DependencyFactory::class, 'fromConnection']);
            $diDefinition->setArgument(1, new Reference('doctrine.migrations.connection_loader'));
        }
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath() : string
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    public function getNamespace() : string
    {
        return 'http://symfony.com/schema/dic/doctrine/migrations/3.0';
    }
}
