<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\DependencyInjection\CompilerPass;

use Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass\RegisterMigrationsPass;
use Doctrine\Bundle\MigrationsBundle\MigrationsRepository\ServiceMigrationsRepository;
use Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\Migrations\Migration001;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;

class RegisterMigrationsPassTest extends TestCase
{
    public function testProcessWhenServiceMigrationsRepositoryIsRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->register('doctrine.migrations.service_migrations_repository', ServiceMigrationsRepository::class)
            ->addArgument(new AbstractArgument());
        $container->register(Migration001::class, Migration001::class)->addTag('doctrine_migrations.migration');

        $pass = new RegisterMigrationsPass();
        $pass->process($container);

        $argument = $container->getDefinition('doctrine.migrations.service_migrations_repository')->getArgument(0);
        self::assertEquals(
            new ServiceLocatorArgument([Migration001::class => new TypedReference(Migration001::class, Migration001::class)]),
            $argument,
        );

        self::assertEquals([
            Connection::class => new BoundArgument(new Reference('doctrine.migrations.connection'), false),
            LoggerInterface::class => new BoundArgument(new Reference('doctrine.migrations.logger'), false),
        ], $container->getDefinition(Migration001::class)->getBindings());
    }

    public function testProcessWhenServiceMigrationsRepositoryIsNotRegistered(): void
    {
        $container = new ContainerBuilder();

        $pass = new RegisterMigrationsPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition('doctrine.migrations.service_migrations_repository'));
    }
}
