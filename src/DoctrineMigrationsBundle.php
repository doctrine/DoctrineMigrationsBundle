<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle;

use Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass\ConfigureDependencyFactoryPass;
use Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass\RegisterMigrationsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function dirname;

/** @final */
class DoctrineMigrationsBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ConfigureDependencyFactoryPass());
        $container->addCompilerPass(new RegisterMigrationsPass());
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
