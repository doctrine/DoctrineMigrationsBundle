<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass\ConfigureDependencyFactoryPass;
use Doctrine\Bundle\MigrationsBundle\DependencyInjection\DoctrineMigrationsExtension;
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
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpKernel\KernelInterface;
use function sys_get_temp_dir;

class DoctrineCommandsTest extends TestCase
{
    /**
     * @dataProvider getCommands
     */
    public function testCommandRegistered(string $name, string $instance) : void
    {
        $application = $this->getApplication();

        self::assertInstanceOf($instance, $application->find($name));
    }

    /**
     * @return string[][]
     */
    public function getCommands() : array
    {
        return [
            ['doctrine:migrations:diff', DiffCommand::class],
            ['doctrine:migrations:dump-schema', DumpSchemaCommand::class],
            ['doctrine:migrations:execute', ExecuteCommand::class],
            ['doctrine:migrations:generate', GenerateCommand::class],
            ['doctrine:migrations:latest', LatestCommand::class],
            ['doctrine:migrations:list', ListCommand::class],
            ['doctrine:migrations:migrate', MigrateCommand::class],
            ['doctrine:migrations:rollup', RollupCommand::class],
            ['doctrine:migrations:status', StatusCommand::class],
            ['doctrine:migrations:sync-metadata-storage', SyncMetadataCommand::class],
            ['doctrine:migrations:up-to-date', UpToDateCommand::class],
            ['doctrine:migrations:version', VersionCommand::class],
        ];
    }

    /**
     * @return MockObject|KernelInterface
     */
    private function getKernel(ContainerBuilder $container)
    {
        $kernel = $this->createMock(KernelInterface::class);

        $kernel
            ->method('getContainer')
            ->willReturn($container);

        $kernel
            ->method('getBundles')
            ->willReturn([]);

        return $kernel;
    }

    private function getApplication() : Application
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.bundles' => [],
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.project_dir' => __DIR__ . '/../',
        ]));

        $kernel      = $this->getKernel($container);
        $application = new Application($kernel);
        $container->set('application', $application);

        $em = $this->createMock(EntityManager::class);
        $container->set('doctrine.orm.default_entity_manager', $em);

        $container->addCompilerPass(new AddConsoleCommandPass());

        $extension = new DoctrineMigrationsExtension();
        $extension->load([
            'doctrine_migrations' => [
                'migrations_paths' => ['DoctrineMigrationsTest' => 'a'],
            ],
        ], $container);

        $container->addCompilerPass(new ConfigureDependencyFactoryPass());
        $container->compile();

        return $application;
    }
}
