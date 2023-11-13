<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection;

use ReflectionClass;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function array_filter;
use function array_keys;
use function constant;
use function count;
use function in_array;
use function is_string;
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

        $rootNode = $treeBuilder->getRootNode();

        $organizeMigrationModes = $this->getOrganizeMigrationsModes();

        $rootNode
            ->fixXmlConfig('migration', 'migrations')
            ->fixXmlConfig('migrations_path', 'migrations_paths')
            ->children()
                ->arrayNode('migrations_paths')
                    ->info('A list of namespace/path pairs where to look for migrations.')
                    ->defaultValue([])
                    ->useAttributeAsKey('namespace')
                    ->prototype('scalar')->end()
                ->end()

                ->arrayNode('services')
                    ->info('A set of services to pass to the underlying doctrine/migrations library, allowing to change its behaviour.')
                    ->useAttributeAsKey('service')
                    ->defaultValue([])
                    ->validate()
                        ->ifTrue(static function (array $v): bool {
                            return count(array_filter(array_keys($v), static function (string $doctrineService): bool {
                                return strpos($doctrineService, 'Doctrine\Migrations\\') !== 0;
                            })) !== 0;
                        })
                        ->thenInvalid('Valid services for the DoctrineMigrationsBundle must be in the "Doctrine\Migrations" namespace.')
                    ->end()
                    ->prototype('scalar')->end()
                ->end()

                ->arrayNode('factories')
                    ->info('A set of callables to pass to the underlying doctrine/migrations library as services, allowing to change its behaviour.')
                    ->useAttributeAsKey('factory')
                    ->defaultValue([])
                    ->validate()
                        ->ifTrue(static function (array $v): bool {
                            return count(array_filter(array_keys($v), static function (string $doctrineService): bool {
                                return strpos($doctrineService, 'Doctrine\Migrations\\') !== 0;
                            })) !== 0;
                        })
                        ->thenInvalid('Valid callables for the DoctrineMigrationsBundle must be in the "Doctrine\Migrations" namespace.')
                    ->end()
                    ->prototype('scalar')->end()
                ->end()

                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                    ->info('Storage to use for migration status metadata.')
                    ->children()
                        ->arrayNode('table_storage')
                            ->addDefaultsIfNotSet()
                            ->info('The default metadata storage, implemented as a table in the database.')
                            ->children()
                                ->scalarNode('table_name')->defaultValue(null)->cannotBeEmpty()->end()
                                ->scalarNode('version_column_name')->defaultValue(null)->end()
                                ->scalarNode('version_column_length')->defaultValue(null)->end()
                                ->scalarNode('executed_at_column_name')->defaultValue(null)->end()
                                ->scalarNode('execution_time_column_name')->defaultValue(null)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('migrations')
                    ->info('A list of migrations to load in addition to the one discovered via "migrations_paths".')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->scalarNode('connection')
                    ->info('Connection name to use for the migrations database.')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('em')
                    ->info('Entity manager name to use for the migrations database (available when doctrine/orm is installed).')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('all_or_nothing')
                    ->info('Run all migrations in a transaction.')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('check_database_platform')
                    ->info('Adds an extra check in the generated migrations to allow execution only on the same platform as they were initially generated on.')
                    ->defaultValue(true)
                ->end()
                ->scalarNode('custom_template')
                    ->info('Custom template path for generated migration classes.')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('organize_migrations')
                    ->defaultValue(false)
                    ->info('Organize migrations mode. Possible values are: "BY_YEAR", "BY_YEAR_AND_MONTH", false')
                    ->validate()
                        ->ifTrue(static function ($v) use ($organizeMigrationModes): bool {
                            if ($v === false) {
                                return false;
                            }

                            return ! is_string($v) || ! in_array(strtoupper($v), $organizeMigrationModes, true);
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
                ->booleanNode('enable_profiler')
                    ->info('Whether or not to enable the profiler collector to calculate and visualize migration status. This adds some queries overhead.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('transactional')
                    ->info('Whether or not to wrap migrations in a single transaction.')
                    ->defaultTrue()
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
        $constsArray = array_keys($refClass->getConstants());
        $namesArray  = [];

        foreach ($constsArray as $constant) {
            if (strpos($constant, $constPrefix) !== 0) {
                continue;
            }

            $namesArray[] = substr($constant, $prefixLen);
        }

        return $namesArray;
    }
}
