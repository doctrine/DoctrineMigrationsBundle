<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection;

use ReflectionClass;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function constant;
use function count;
use function in_array;
use function is_string;
use function method_exists;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;

/**
 * DoctrineMigrationsExtension configuration structure.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder The config tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_migrations');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('doctrine_migrations', 'array');
        }

        $organizeMigrationModes = $this->getOrganizeMigrationsModes();

        $rootNode
            ->children()
                ->scalarNode('name')
                    ->setDeprecated(...$this->getDeprecationParams('The "%node%" option is deprecated.'))
                    ->defaultValue('Application Migrations')
                ->end()

                // 3.x forward compatibility layer
                ->arrayNode('migrations_paths')
                    ->info('A list of pairs namespace/path where to look for migrations.')
                    ->useAttributeAsKey('name')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                    ->validate()
                        ->ifTrue(static function ($v): bool {
                            return count($v) === 0;
                        })
                        ->thenInvalid('At least one migration path must be specified.')

                        ->ifTrue(static function ($v): bool {
                            return count($v) >  1;
                        })
                        ->thenInvalid('Maximum one migration path can be specified with the 2.x version.')
                    ->end()
                ->end()

                ->arrayNode('storage')
                    ->info('Storage to use for migration status metadata.')
                    ->children()
                        ->arrayNode('table_storage')
                            ->info('The default metadata storage, implemented as database table.')
                            ->children()
                                ->scalarNode('table_name')->defaultValue(null)->cannotBeEmpty()->end()
                                ->scalarNode('version_column_name')->defaultValue(null)->end()
                                ->scalarNode('version_column_length')
                                    ->defaultValue(null)
                                    ->validate()
                                        ->ifTrue(static function ($v): bool {
                                            return $v < 1024;
                                        })
                                        ->thenInvalid('The minimum length for the version column is 1024.')
                                    ->end()
                                ->end()
                                ->scalarNode('executed_at_column_name')->defaultValue(null)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->scalarNode('dir_name')
                    ->defaultValue('%kernel.root_dir%/DoctrineMigrations')->cannotBeEmpty()
                    ->setDeprecated(...$this->getDeprecationParams('The "%node%" option is deprecated. Use "migrations_paths" instead.'))
                ->end()
                ->scalarNode('namespace')
                    ->defaultValue('Application\Migrations')->cannotBeEmpty()
                    ->setDeprecated(...$this->getDeprecationParams('The "%node%" option is deprecated. Use "migrations_paths" instead.'))
                ->end()
                ->scalarNode('table_name')
                    ->defaultValue('migration_versions')->cannotBeEmpty()
                    ->setDeprecated(...$this->getDeprecationParams('The "%node%" option is deprecated. Use "storage.table_storage.table_name" instead.'))
                ->end()
                ->scalarNode('column_name')
                    ->defaultValue('version')
                    ->setDeprecated(...$this->getDeprecationParams('The "%node%" option is deprecated. Use "storage.table_storage.version_column_name" instead.'))
                ->end()
                ->scalarNode('column_length')
                    ->defaultValue(14)
                    ->setDeprecated(...$this->getDeprecationParams('The "%node%" option is deprecated. Use "storage.table_storage.version_column_length" instead.'))
                ->end()
                ->scalarNode('executed_at_column_name')
                    ->defaultValue('executed_at')
                    ->setDeprecated(...$this->getDeprecationParams('The "%node%" option is deprecated. Use "storage.table_storage.executed_at_column_name" instead.'))
                ->end()
                ->scalarNode('all_or_nothing')->defaultValue(false)->end()
                ->scalarNode('custom_template')->defaultValue(null)->end()
                ->scalarNode('organize_migrations')->defaultValue(false)
                    ->info('Organize migrations mode. Possible values are: "BY_YEAR", "BY_YEAR_AND_MONTH", false')
                    ->validate()
                        ->ifTrue(static function ($v) use ($organizeMigrationModes) {
                            if ($v === false) {
                                return false;
                            }

                            return ! is_string($v) || ! in_array(strtoupper($v), $organizeMigrationModes);
                        })
                        ->thenInvalid('Invalid organize migrations mode value %s')
                    ->end()
                    ->validate()
                        ->ifString()
                            ->then(static function ($v) {
                                return constant('Doctrine\Migrations\Configuration\Configuration::VERSIONS_ORGANIZATION_' . strtoupper($v));
                            })
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * Find organize migrations modes for their names
     *
     * @return string[]
     */
    private function getOrganizeMigrationsModes(): array
    {
        $constPrefix = 'VERSIONS_ORGANIZATION_';
        $prefixLen   = strlen($constPrefix);
        $refClass    = new ReflectionClass('Doctrine\Migrations\Configuration\Configuration');
        $constsArray = $refClass->getConstants();
        $namesArray  = [];

        foreach ($constsArray as $key => $value) {
            if (strpos($key, $constPrefix) !== 0) {
                continue;
            }

            $namesArray[] = substr($key, $prefixLen);
        }

        return $namesArray;
    }

    /**
     * Returns the correct deprecation params as an array for setDeprecated().
     *
     * symfony/config v5.1 introduces a deprecation notice when calling
     * setDeprecated() with less than 3 args and the getDeprecation() method was
     * introduced at the same time. By checking if getDeprecation() exists,
     * we can determine the correct param count to use when calling setDeprecated().
     *
     * @return string[]
     */
    private function getDeprecationParams(string $message): array
    {
        if (method_exists(BaseNode::class, 'getDeprecation')) {
            return [
                'doctrine/doctrine-migrations-bundle',
                '2.2',
                $message,
            ];
        }

        return [$message];
    }
}
