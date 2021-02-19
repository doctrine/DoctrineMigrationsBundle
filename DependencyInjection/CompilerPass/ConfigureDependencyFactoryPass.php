<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass;

use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function assert;
use function is_string;
use function sprintf;

class ConfigureDependencyFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $diDefinition = $container->getDefinition('doctrine.migrations.dependency_factory');
        $preferredEm  = $container->getParameter('doctrine.migrations.preferred_em');
        if ($container->has('doctrine')) {
            $loaderDefinition = $container->getDefinition('doctrine.migrations.registry_loader');
            $loaderDefinition->setArgument(0, new Reference('doctrine'));
            if ($preferredEm !== null) {
                $loaderDefinition->setArgument(1, $preferredEm);
            }

            $diDefinition->setFactory([DependencyFactory::class, 'fromEntityManager']);
            $diDefinition->setArgument(1, new Reference('doctrine.migrations.registry_loader'));

            return;
        }

        assert(is_string($preferredEm) || $preferredEm === null);
        $emID = sprintf('doctrine.orm.%s_entity_manager', $preferredEm ?? 'default');
        if ($container->has($emID)) {
            $container->getDefinition('doctrine.migrations.em_loader')
                ->setArgument(0, new Reference($emID));

            $diDefinition->setFactory([DependencyFactory::class, 'fromEntityManager']);
            $diDefinition->setArgument(1, new Reference('doctrine.migrations.em_loader'));

            return;
        }

        $preferredConnection = $container->getParameter('doctrine.migrations.preferred_connection');
            assert(is_string($preferredConnection) || $preferredConnection === null);
            $connectionId = sprintf('doctrine.dbal.%s_connection', $preferredConnection ?? 'default');
        $container->getDefinition('doctrine.migrations.connection_loader')
            ->setArgument(0, new Reference($connectionId));

        $diDefinition->setFactory([DependencyFactory::class, 'fromConnection']);
        $diDefinition->setArgument(1, new Reference('doctrine.migrations.connection_loader'));
    }
}
