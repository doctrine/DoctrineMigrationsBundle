<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;

class RegisterMigrationsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $migrationRefs = [];

        foreach ($container->findTaggedServiceIds('doctrine_migrations.migration', true) as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $definition->setBindings([
                Connection::class => new BoundArgument(new Reference('doctrine.migrations.connection')),
                LoggerInterface::class => new BoundArgument(new Reference('doctrine.migrations.logger')),
            ]);

            $migrationRefs[$id] = new TypedReference($id, $definition->getClass());
        }

        if ($migrationRefs !== []) {
            $container->getDefinition('doctrine.migrations.filter_service_migration_finder')
                ->replaceArgument(1, new ServiceLocatorArgument($migrationRefs));
            $container->getDefinition('doctrine.migrations.service_migrations_repository')
                ->replaceArgument(1, new ServiceLocatorArgument($migrationRefs));
        } else {
            $container->removeDefinition('doctrine.migrations.connection');
            $container->removeDefinition('doctrine.migrations.logger');
            $container->removeDefinition('doctrine.migrations.filter_service_migration_finder');
            $container->removeDefinition('doctrine.migrations.service_migrations_repository');
        }
    }
}
