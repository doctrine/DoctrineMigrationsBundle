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
use Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration;

/**
 * Base class for Doctrine console commands to extend from.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class DoctrineCommand extends BaseCommand
{
    public static function configureMigrations(ContainerInterface $container, Configuration $configuration)
    {
        if (!$configuration->getMigrationsDirectory()) {
            $dir = $container->getParameter('doctrine_migrations.dir_name');
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $configuration->setMigrationsDirectory($dir);
        } else {
            $dir = $configuration->getMigrationsDirectory();
            // class Kernel has method getKernelParameters with some of the important path parameters
            $pathPlaceholderArray = array('kernel.root_dir', 'kernel.cache_dir', 'kernel.logs_dir');
            foreach ($pathPlaceholderArray as $pathPlaceholder) {
                if ($container->hasParameter($pathPlaceholder) && preg_match('/\%'.$pathPlaceholder.'\%/', $dir)) {
                    $dir = str_replace('%'.$pathPlaceholder.'%', $container->getParameter($pathPlaceholder), $dir);
                }
            }
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $configuration->setMigrationsDirectory($dir);
        }
        if (!$configuration->getMigrationsNamespace()) {
            $configuration->setMigrationsNamespace($container->getParameter('doctrine_migrations.namespace'));
        }
        if (!$configuration->getName()) {
            $configuration->setName($container->getParameter('doctrine_migrations.name'));
        }
        // For backward compatibility, need use a table from parameters for overwrite the default configuration
        if (!$configuration->getMigrationsTableName() || !($configuration instanceof AbstractFileConfiguration)) {
            $configuration->setMigrationsTableName($container->getParameter('doctrine_migrations.table_name'));
        }
        // Migrations is not register from configuration loader
        if (!($configuration instanceof AbstractFileConfiguration)) {
            $configuration->registerMigrationsFromDirectory($configuration->getMigrationsDirectory());
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
