<?php

namespace Doctrine\Bundle\MigrationsBundle;

use Doctrine\Bundle\MigrationsBundle\DependencyInjection\CompilerPass\ConfigureDependencyFactoryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class DoctrineMigrationsBundle extends Bundle
{
    /** @return void */
    public function build(ContainerBuilder $container)
    {
      $container->addCompilerPass(new ConfigureDependencyFactoryPass());
    }
}
