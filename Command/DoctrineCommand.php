<?php

/*
 * This file is part of the Doctrine MigrationsBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MigrationsBundle\Command;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand as BaseCommand;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

/**
 * Base class for Doctrine console commands to extend from.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class DoctrineCommand extends BaseCommand
{
    public static function configureMigrations(ContainerInterface $container, Configuration $configuration)
    {
        $dir = $configuration->getMigrationsDirectory();

        if (is_null($dir)) {
            $dir = $container->getParameter('doctrine_migrations.dir_name');

            $configuration->setMigrationsDirectory($dir);
            $configuration->registerMigrationsFromDirectory($dir);
        }

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (is_null($configuration->getMigrationsNamespace())) {
            $configuration->setMigrationsNamespace($container->getParameter('doctrine_migrations.namespace'));
        }

        if (is_null($configuration->getName())) {
            $configuration->setName($container->getParameter('doctrine_migrations.name'));
        }

        if ($configuration->getMigrationsTableName() == 'doctrine_migration_versions')
        {
            $configuration->setMigrationsTableName($container->getParameter('doctrine_migrations.table_name'));
        }

        self::injectContainerToMigrations($container, $configuration->getMigrations());
    }

    /**
     * Injects the container to migrations aware of it
     */
    private static function injectContainerToMigrations(ContainerInterface $container, array $versions)
    {
        foreach ($versions as $version) {
            $migration = $version->getMigration();
            if ($migration instanceof ContainerAwareInterface) {
                $migration->setContainer($container);
            }
        }
    }
}
