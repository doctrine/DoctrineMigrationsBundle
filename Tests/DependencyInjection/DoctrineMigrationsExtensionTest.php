<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\CacheCompatibilityPass;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Bundle\MigrationsBundle\DependencyInjection\DoctrineMigrationsExtension;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\Migrations\ContainerAwareMigration;
use Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\TestBundle\TestBundle;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\MissingDependency;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\VarExporter\LazyGhostTrait;

use function assert;
use function interface_exists;
use function sys_get_temp_dir;
use function trait_exists;

class DoctrineMigrationsExtensionTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testXmlConfigs(): void
    {
        $container = $this->getContainerBuilder();

        $container->registerExtension(new DoctrineMigrationsExtension());

        $container->setAlias('doctrine.migrations.configuration.test', new Alias('doctrine.migrations.configuration', true));

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Fixtures'));
        $loader->load('conf.xml');

        $container->compile();

        $config = $container->get('doctrine.migrations.configuration.test');
        $this->assertConfigs($config);
    }

    public function testFullConfig(): void
    {
        $config = [
            'storage' => [
                'table_storage' => [
                    'table_name'                 => 'doctrine_migration_versions_test',
                    'version_column_name'        => 'doctrine_migration_column_test',
                    'version_column_length'      => 2000,
                    'executed_at_column_name'    => 'doctrine_migration_executed_at_column_test',
                    'execution_time_column_name' => 'doctrine_migration_execution_time_column_test',
                ],
            ],

            'migrations_paths' => [
                'DoctrineMigrationsTest' => 'a',
                'DoctrineMigrationsTest2' => 'b',
            ],

            'migrations' => ['Foo', 'Bar'],

            'organize_migrations' => 'BY_YEAR_AND_MONTH',

            'all_or_nothing'            => true,
            'check_database_platform'   => true,
        ];
        $container = $this->getContainer($config);

        $container->compile();

        $config = $container->get('doctrine.migrations.configuration');

        $this->assertConfigs($config);
    }

    public function testNoConfigsAreNeeded(): void
    {
        $container = $this->getContainer([]);

        $container->compile();

        $config = $container->get('doctrine.migrations.configuration');

        self::assertInstanceOf(Configuration::class, $config);
        self::assertSame([], $config->getMigrationDirectories());
    }

    public function testBundleRelativePathResolution(): void
    {
        $container = $this->getContainer([
            'migrations_paths' => [
                'DoctrineMigrationsTest' => '@TestBundle',
                'DoctrineMigrationsTestAnother' => '@TestBundle/another-path',
            ],
        ]);

        $container->compile();

        $config = $container->get('doctrine.migrations.configuration');
        $bundle = new TestBundle();

        self::assertInstanceOf(Configuration::class, $config);
        self::assertSame([
            'DoctrineMigrationsTest' => $bundle->getPath(),
            'DoctrineMigrationsTestAnother' => $bundle->getPath() . '/another-path',

        ], $config->getMigrationDirectories());
    }

    private function assertConfigs(?object $config): void
    {
        self::assertInstanceOf(Configuration::class, $config);
        self::assertSame([
            'DoctrineMigrationsTest' => 'a',
            'DoctrineMigrationsTest2' => 'b',

        ], $config->getMigrationDirectories());

        self::assertSame(['Foo', 'Bar'], $config->getMigrationClasses());
        self::assertTrue($config->isAllOrNothing());
        self::assertTrue($config->isDatabasePlatformChecked());
        self::assertTrue($config->areMigrationsOrganizedByYearAndMonth());

        $storage = $config->getMetadataStorageConfiguration();
        self::assertInstanceOf(TableMetadataStorageConfiguration::class, $storage);

        self::assertSame('doctrine_migration_versions_test', $storage->getTableName());
        self::assertSame('doctrine_migration_column_test', $storage->getVersionColumnName());
        self::assertSame(2000, $storage->getVersionColumnLength());
        self::assertSame('doctrine_migration_execution_time_column_test', $storage->getExecutionTimeColumnName());
        self::assertSame('doctrine_migration_executed_at_column_test', $storage->getExecutedAtColumnName());
    }

    public function testCustomSorter(): void
    {
        $config    = [
            'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],
            'services' => [Comparator::class => 'my_sorter'],
        ];
        $container = $this->getContainer($config);

        $sorter = new class () implements Comparator{
            public function compare(Version $a, Version $b): int
            {
                return 1;
            }
        };
        $container->set('my_sorter', $sorter);

        $container->compile();

        $di = $container->get('doctrine.migrations.dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $di);
        self::assertSame($sorter, $di->getVersionComparator());
    }

    /** @group legacy */
    public function testContainerAwareMigrations(): void
    {
        if (! interface_exists(ContainerAwareInterface::class)) {
            self::markTestSkipped('This test requires Symfony < 7');
        }

        $config    = [
            'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],
        ];
        $container = $this->getContainer($config);

        $container->compile();

        $di = $container->get('doctrine.migrations.dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $di);

        $this->expectDeprecation('Since doctrine/doctrine-migrations-bundle 3.3: Migration "Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\Migrations\ContainerAwareMigration" implements "Symfony\Component\DependencyInjection\ContainerAwareInterface" to gain access to the application\'s service container. This method is deprecated and won\'t work with Symfony 7.');

        $migration = $di->getMigrationFactory()->createVersion(ContainerAwareMigration::class);

        self::assertInstanceOf(ContainerAwareMigration::class, $migration);
        self::assertSame($container, $migration->getContainer());
    }

    public function testServicesAreLazy(): void
    {
        $config    = [
            'services' => [Comparator::class => 'my_sorter'],
        ];
        $container = $this->getContainer($config);

        $sorterFactory = new class () {
            public function __invoke(): void
            {
                throw new Exception('This method should not be invoked.');
            }
        };
        $container->set('my_sorter_factory', $sorterFactory);

        $sorterDefinition = new Definition(Comparator::class);
        $sorterDefinition->setFactory(new Reference('my_sorter_factory'));
        $container->setDefinition('my_sorter', $sorterDefinition);

        $container->compile();

        $di = $container->get('doctrine.migrations.dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $di);
    }

    public function testServiceFactory(): void
    {
        $mockComparator = $this->createMock(Comparator::class);
        $config         = [
            'factories' => [Comparator::class => 'my_sorter'],
        ];

        $container = $this->getContainer($config);

        $sorterFactory = new class ($mockComparator) {
            /** @var Comparator */
            private $comparator;

            public function __construct(Comparator $comparator)
            {
                $this->comparator = $comparator;
            }

            public function __invoke(DependencyFactory $di): Comparator
            {
                return $this->comparator;
            }
        };
        $container->set('my_sorter', $sorterFactory);

        $container->compile();

        $di = $container->get('doctrine.migrations.dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $di);
        self::assertSame($mockComparator, $di->getVersionComparator());
    }

    public function testCustomConnection(): void
    {
        $config    = [
            'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],
            'connection' => 'custom',
        ];
        $container = $this->getContainer($config, [
            'connections' => [
                'custom' => ['url' => 'sqlite:///:memory:'],
                'another' => ['url' => 'sqlite:///:memory:'],
            ],
        ]);

        $container->compile();
        $doctrine = $container->get('doctrine');
        assert($doctrine instanceof Registry);

        $di = $container->get('doctrine.migrations.dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $di);
        self::assertSame($doctrine->getConnection('custom'), $di->getConnection());
    }

    public function testPrefersEntityManagerOverConnection(): void
    {
        $config    = [
            'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],
        ];
        $ormConfig = trait_exists(LazyGhostTrait::class) ? ['enable_lazy_ghost_objects' => true] : [];
        $container = $this->getContainer($config, null, $ormConfig);

        $container->compile();

        $di = $container->get('doctrine.migrations.dependency_factory');

        self::assertInstanceOf(DependencyFactory::class, $di);
        $di->getEntityManager();
    }

    public function testNoEntityManagersConfigured(): void
    {
        $config    = ['em' => null];
        $container = $this->getContainer($config);

        $container->compile();

        $di = $container->get('doctrine.migrations.dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $di);

        $this->expectException(MissingDependency::class);
        $di->getEntityManager();
    }

    public function testManagerRegistryThrowsExceptionOnUnknownEm(): void
    {
        $container = $this->getContainer(['em' => 'foo']);
        $this->expectException(InvalidArgumentException::class);
        $container->compile();
    }

    public function testConnectionRegistryThrowsExceptionOnUnknownConnection(): void
    {
        $container = $this->getContainer(['connection' => 'bar']);
        $this->expectException(InvalidArgumentException::class);
        $container->compile();
    }

    public function testRequiresDoctrineBundle(): void
    {
        $container = $this->getContainerBuilder();

        $bundle = new DoctrineMigrationsBundle();
        $bundle->build($container);

        $extension = new DoctrineMigrationsExtension();
        $extension->load([], $container);

        $this->expectException(RuntimeException::class);
        $container->compile();
    }

    public function testCustomEntityManager(): void
    {
        $config    = [
            'em' => 'custom',
            'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],
        ];
        $ormConfig = [
            'entity_managers' => [
                'custom' => null,
                'acb' => null,
            ],
        ];
        if (trait_exists(LazyGhostTrait::class)) {
            $ormConfig['enable_lazy_ghost_objects'] = true;
        }

        $container = $this->getContainer($config, null, $ormConfig);

        $container->compile();

        $di = $container->get('doctrine.migrations.dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $di);
        $doctrine = $container->get('doctrine');
        assert($doctrine instanceof Registry);
        $em = $di->getEntityManager();

        self::assertSame($doctrine->getManager('custom'), $em);
    }

    public function testCustomMetadataStorage(): void
    {
        $config = [
            'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],
            'services' => [MetadataStorage::class => 'mock_storage_service'],
        ];

        $container = $this->getContainer($config);

        $mockStorage = $this->createMock(MetadataStorage::class);
        $container->set('mock_storage_service', $mockStorage);

        $container->compile();

        $di = $container->get('doctrine.migrations.dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $di);
        self::assertSame($mockStorage, $di->getMetadataStorage());
    }

    public function testInvalidService(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "doctrine_migrations.services": Valid services for the DoctrineMigrationsBundle must be in the "Doctrine\Migrations" namespace.');

        $config    = [
            'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],
            'services' => ['foo' => 'mock_storage_service'],
        ];
        $container = $this->getContainer($config);
        $container->compile();
    }

    public function testCanNotSpecifyBothEmAndConnection(): void
    {
        $this->expectExceptionMessage('You cannot specify both "connection" and "em" in the DoctrineMigrationsBundle configurations');
        $this->expectException(InvalidArgumentException::class);

        $config = [
            'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],
            'em' => 'custom',
            'connection' => 'custom',
        ];

        $container = $this->getContainer($config);

        $container->compile();
    }

    public function testTransactionalIsTrueIfNotSet(): void
    {
        $container = $this->getContainerBuilder();

        $container->registerExtension(new DoctrineMigrationsExtension());
        $container->setAlias('doctrine.migrations.configuration.test', new Alias('doctrine.migrations.configuration', true));

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Fixtures'));
        $loader->load('conf.xml');

        $container->compile();

        $config = $container->get('doctrine.migrations.configuration.test');
        assert($config instanceof Configuration);

        self::assertTrue($config->isTransactional());
    }

    public function testTransactionalSetToFalseReflectsInConfig(): void
    {
        $config    = ['transactional' => false];
        $container = $this->getContainer($config);
        $container->compile();

        $config = $container->get('doctrine.migrations.configuration');
        assert($config instanceof Configuration);

        self::assertFalse($config->isTransactional());
    }

    /**
     * @param mixed[]      $config
     * @param mixed[]|null $dbalConfig
     * @param mixed[]|null $ormConfig
     */
    private function getContainer(array $config, ?array $dbalConfig = null, ?array $ormConfig = null): ContainerBuilder
    {
        $container = $this->getContainerBuilder();

        $bundle = new DoctrineMigrationsBundle();
        $bundle->build($container);

        $extension = new DoctrineMigrationsExtension();
        $extension->load(['doctrine_migrations' => $config], $container);

        $extension = new DoctrineExtension();

        $doctrineBundleConfigs = $dbalConfig === null ? ['dbal' => ['url' => 'sqlite:///:memory:']] : ['dbal' => $dbalConfig];
        if ($ormConfig !== null) {
            $doctrineBundleConfigs += ['orm' => $ormConfig];
        }

        $extension->load(['doctrine' => $doctrineBundleConfigs], $container);

        $container->getDefinition('doctrine.migrations.dependency_factory')->setPublic(true);
        $container->getDefinition('doctrine.migrations.configuration')->setPublic(true);
        $container->addCompilerPass(new CacheCompatibilityPass());

        return $container;
    }

    private function getContainerBuilder(): ContainerBuilder
    {
        $bundle = new TestBundle();

        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.bundles' => [$bundle->getName() => TestBundle::class],
            'kernel.bundles_metadata' => [
                $bundle->getName() => [
                    'path' => $bundle->getPath(),
                    'namespace' => $bundle->getNamespace(),
                ],
            ],
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.project_dir' => __DIR__ . '/../',
            'kernel.root_dir' => __DIR__ ,
        ]));
    }
}
