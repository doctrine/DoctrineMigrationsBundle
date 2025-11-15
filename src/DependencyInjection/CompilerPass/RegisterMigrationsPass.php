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

/** @internal */
final class RegisterMigrationsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('doctrine.migrations.service_migrations_repository')) {
            return;
        }

        $migrationRefs = [];

        foreach ($container->findTaggedServiceIds('doctrine_migrations.migration', true) as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $definition->setBindings([
                Connection::class => new BoundArgument(new Reference('doctrine.migrations.connection'), false),
                LoggerInterface::class => new BoundArgument(new Reference('doctrine.migrations.logger'), false),
            ]);

            $migrationRefs[$id] = new TypedReference($id, $definition->getClass());
        }

        $container->getDefinition('doctrine.migrations.service_migrations_repository')
            ->replaceArgument(0, new ServiceLocatorArgument($migrationRefs));
    }
}
