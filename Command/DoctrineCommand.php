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

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand as BaseCommand;
use Doctrine\DBAL\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

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
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                $error = error_get_last();
                throw new \ErrorException($error['message']);
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
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                $error = error_get_last();
                throw new \ErrorException($error['message']);
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
        if (!($configuration instanceof AbstractFileConfiguration) || !$configuration->getMigrationsTableName()) {
            $configuration->setMigrationsTableName($container->getParameter('doctrine_migrations.table_name'));
        }
        // Migrations is not register from configuration loader
        if (!($configuration instanceof AbstractFileConfiguration)) {
            $configuration->registerMigrationsFromDirectory($configuration->getMigrationsDirectory());
        }

        $organizeMigrations = $container->getParameter('doctrine_migrations.organize_migrations');
        switch ($organizeMigrations) {
            case Configuration::VERSIONS_ORGANIZATION_BY_YEAR:
                $configuration->setMigrationsAreOrganizedByYear(true);
                break;

            case Configuration::VERSIONS_ORGANIZATION_BY_YEAR_AND_MONTH:
                $configuration->setMigrationsAreOrganizedByYearAndMonth(true);
                break;

            case false:
                break;

            default:
                throw new InvalidArgumentException('Invalid value for "doctrine_migrations.organize_migrations" parameter.');
        }

        self::injectContainerToMigrations($container, $configuration->getMigrations());
    }

    /**
     * @param ContainerInterface $container
     * @param array $versions
     *
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
