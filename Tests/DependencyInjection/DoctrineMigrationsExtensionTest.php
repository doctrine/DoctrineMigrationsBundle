<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MigrationsBundle\DependencyInjection\DoctrineMigrationsExtension;
use Doctrine\Migrations\Configuration\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use function sys_get_temp_dir;

class DoctrineMigrationsExtensionTest extends TestCase
{
    public function testOrganizeMigrations() : void
    {
        $container = $this->getContainer();
        $extension = new DoctrineMigrationsExtension();

        $config = ['organize_migrations' => 'BY_YEAR'];

        $extension->load(['doctrine_migrations' => $config], $container);

        $this->assertEquals(
            Configuration::VERSIONS_ORGANIZATION_BY_YEAR,
            $container->getParameter('doctrine_migrations.organize_migrations')
        );
    }

    public function testForwardCompatibilityLayer() : void
    {
        $container = $this->getContainer();
        $extension = new DoctrineMigrationsExtension();

        $config = [
            'storage' => [
                'table_storage' => [
                    'table_name'                 => 'doctrine_migration_versions_test',
                    'version_column_name'        => 'doctrine_migration_column_test',
                    'version_column_length'      => 2000,
                    'executed_at_column_name'    => 'doctrine_migration_executed_at_column_test',
                ],
            ],

            'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],

        ];

        $extension->load(['doctrine_migrations' => $config], $container);

        $this->assertEquals('a', $container->getParameter('doctrine_migrations.dir_name'));
        $this->assertEquals('DoctrineMigrationsTest', $container->getParameter('doctrine_migrations.namespace'));
        $this->assertEquals('doctrine_migration_versions_test', $container->getParameter('doctrine_migrations.table_name'));
        $this->assertEquals('doctrine_migration_column_test', $container->getParameter('doctrine_migrations.column_name'));
        $this->assertEquals(2000, $container->getParameter('doctrine_migrations.column_length'));
        $this->assertEquals(2000, $container->getParameter('doctrine_migrations.column_length'));
        $this->assertEquals('doctrine_migration_executed_at_column_test', $container->getParameter('doctrine_migrations.executed_at_column_name'));
    }

    private function getContainer() : ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.bundles' => [],
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../', // src dir
        ]));
    }
}
