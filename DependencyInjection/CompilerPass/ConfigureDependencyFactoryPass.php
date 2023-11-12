<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass;

use Doctrine\Migrations\DependencyFactory;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function array_keys;
use function assert;
use function count;
use function implode;
use function is_array;
use function is_string;
use function sprintf;

class ConfigureDependencyFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has('doctrine')) {
            throw new RuntimeException('DoctrineMigrationsBundle requires DoctrineBundle to be enabled.');
        }

        $diDefinition = $container->getDefinition('doctrine.migrations.dependency_factory');

        $preferredConnection = $container->getParameter('doctrine.migrations.preferred_connection');
        assert(is_string($preferredConnection) || $preferredConnection === null);
        // explicitly use configured connection
        if ($preferredConnection !== null) {
            $this->validatePreferredConnection($container, $preferredConnection);

            $loaderDefinition = $container->getDefinition('doctrine.migrations.connection_registry_loader');
            $loaderDefinition->setArgument(1, $preferredConnection);

            $diDefinition->setFactory([DependencyFactory::class, 'fromConnection']);
            $diDefinition->setArgument(1, new Reference('doctrine.migrations.connection_registry_loader'));

            return;
        }

        $preferredEm = $container->getParameter('doctrine.migrations.preferred_em');
        assert(is_string($preferredEm) || $preferredEm === null);
        // explicitly use configured entity manager
        if ($preferredEm !== null) {
            $this->validatePreferredEm($container, $preferredEm);

            $loaderDefinition = $container->getDefinition('doctrine.migrations.entity_manager_registry_loader');
            $loaderDefinition->setArgument(1, $preferredEm);

            $diDefinition->setFactory([DependencyFactory::class, 'fromEntityManager']);
            $diDefinition->setArgument(1, new Reference('doctrine.migrations.entity_manager_registry_loader'));

            return;
        }

        // try to use any/default entity manager
        if (
            $container->hasParameter('doctrine.entity_managers')
            && is_array($container->getParameter('doctrine.entity_managers'))
            && count($container->getParameter('doctrine.entity_managers')) > 0
        ) {
            $diDefinition->setFactory([DependencyFactory::class, 'fromEntityManager']);
            $diDefinition->setArgument(1, new Reference('doctrine.migrations.entity_manager_registry_loader'));

            return;
        }

        // fallback on any/default connection
        $diDefinition->setFactory([DependencyFactory::class, 'fromConnection']);
        $diDefinition->setArgument(1, new Reference('doctrine.migrations.connection_registry_loader'));
    }

    private function validatePreferredConnection(ContainerBuilder $container, string $preferredConnection): void
    {
        /** @var array<string, string> $allowedConnections */
        $allowedConnections = $container->getParameter('doctrine.connections');
        if (! isset($allowedConnections[$preferredConnection])) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" connection is not defined. Did you mean one of the following: %s',
                $preferredConnection,
                implode(', ', array_keys($allowedConnections))
            ));
        }
    }

    private function validatePreferredEm(ContainerBuilder $container, string $preferredEm): void
    {
        if (
            ! $container->hasParameter('doctrine.entity_managers')
            || ! is_array($container->getParameter('doctrine.entity_managers'))
            || count($container->getParameter('doctrine.entity_managers')) === 0
        ) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" entity manager is not defined. It seems that you do not have configured any entity manager in the DoctrineBundle.',
                $preferredEm
            ));
        }

        /** @var array<string, string> $allowedEms */
        $allowedEms = $container->getParameter('doctrine.entity_managers');
        if (! isset($allowedEms[$preferredEm])) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" entity manager is not defined. Did you mean one of the following: %s',
                $preferredEm,
                implode(', ', array_keys($allowedEms))
            ));
        }
    }
}
