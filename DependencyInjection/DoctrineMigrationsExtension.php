<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function count;
use function current;
use function key;

/**
 * DoctrineMigrationsExtension.
 */
class DoctrineMigrationsExtension extends Extension
{
    /**
     * Responds to the migrations configuration parameter.
     *
     * @param mixed[][] $configs
     *
     * @psalm-param array<string, array<string, array<string, string>|string>>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        // 3.x forward compatibility layer
        if (isset($config['migrations_paths']) && count($config['migrations_paths']) > 0) {
            $config['namespace'] = key($config['migrations_paths']);
            $config['dir_name']  = current($config['migrations_paths']);
            unset($config['migrations_paths']);
        }

        if (isset($config['storage']['table_storage'])) {
            $storageConfig = $config['storage']['table_storage'];
            if (isset($storageConfig['table_name'])) {
                $config['table_name'] = $storageConfig['table_name'];
            }

            if (isset($storageConfig['version_column_name'])) {
                $config['column_name'] = $storageConfig['version_column_name'];
            }

            if (isset($storageConfig['version_column_length'])) {
                $config['column_length'] = $storageConfig['version_column_length'];
            }

            if (isset($storageConfig['executed_at_column_name'])) {
                $config['executed_at_column_name'] = $storageConfig['executed_at_column_name'];
            }

            unset($config['storage']);
        }

        foreach ($config as $key => $value) {
            $container->setParameter($this->getAlias() . '.' . $key, $value);
        }

        $locator = new FileLocator(__DIR__ . '/../Resources/config/');
        $loader  = new XmlFileLoader($container, $locator);

        $loader->load('services.xml');
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath(): string
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    public function getNamespace(): string
    {
        return 'http://symfony.com/schema/dic/doctrine/migrations';
    }
}
