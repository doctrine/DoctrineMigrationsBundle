<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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

        foreach ($config as $key => $value) {
            $container->setParameter($this->getAlias() . '.' . $key, $value);
        }

        $locator = new FileLocator(__DIR__ . '/../Resources/config/');
        $loader  = new XmlFileLoader($container, $locator);

        $loader->load('services.xml');

        $this->configureSchemaProvider($config, $container);
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
        return 'http://symfony.com/schema/dic/doctrine/migrations';
    }

    /**
     * @param string[][] $configs
     */
    private function configureSchemaProvider(array $config, ContainerBuilder $container) : void
    {
        if (! $config['schema_provider']) {
            return;
        }

        $diffCommand = $container->findDefinition('doctrine_migrations.diff_command');
        $diffCommand->setArgument(0, new Reference($config['schema_provider']));
    }
}
