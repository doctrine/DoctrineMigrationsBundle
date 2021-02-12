<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand as BaseCommand;
use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Version\Version;
use ErrorException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

use function assert;
use function error_get_last;
use function is_bool;
use function is_dir;
use function is_int;
use function is_string;
use function mkdir;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_replace;

/**
 * Base class for Doctrine console commands to extend from.
 */
abstract class DoctrineCommand extends BaseCommand
{
    public static function configureMigrations(ContainerInterface $container, Configuration $configuration): void
    {
        $dir = $configuration->getMigrationsDirectory();

        if ($dir === null) {
            $dir = self::getStringParameter($container, 'doctrine_migrations.dir_name');

            if (! is_dir($dir) && ! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
                $error = error_get_last();

                throw new ErrorException(sprintf(
                    'Failed to create directory "%s" with message "%s"',
                    $dir,
                    $error['message']
                ));
            }

            $configuration->setMigrationsDirectory($dir);
        } else {
            // class Kernel has method getKernelParameters with some of the important path parameters
            $pathPlaceholderArray = ['kernel.root_dir', 'kernel.cache_dir', 'kernel.logs_dir'];

            foreach ($pathPlaceholderArray as $pathPlaceholder) {
                if (! $container->hasParameter($pathPlaceholder) || preg_match('/\%' . preg_quote($pathPlaceholder) . '\%/', $dir) === 0) {
                    continue;
                }

                $dir = str_replace('%' . $pathPlaceholder . '%', self::getStringParameter(
                    $container,
                    $pathPlaceholder
                ), $dir);
            }

            if (! is_dir($dir) && ! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
                $error = error_get_last();

                throw new ErrorException(sprintf(
                    'Failed to create directory "%s" with message "%s"',
                    $dir,
                    $error['message']
                ));
            }

            $configuration->setMigrationsDirectory($dir);
        }

        if ($configuration->getMigrationsNamespace() === null) {
            $configuration->setMigrationsNamespace(self::getStringParameter(
                $container,
                'doctrine_migrations.namespace'
            ));
        }

        if ($configuration->getName() === null) {
            $configuration->setName(self::getStringParameter(
                $container,
                'doctrine_migrations.name'
            ));
        }

        // For backward compatibility, need use a table from parameters for overwrite the default configuration
        if (! ($configuration instanceof AbstractFileConfiguration) || $configuration->getMigrationsTableName() === '') {
            $configuration->setMigrationsTableName(self::getStringParameter($container, 'doctrine_migrations.table_name'));
        }

        $configuration->setMigrationsColumnName(self::getStringParameter($container, 'doctrine_migrations.column_name'));
        $columnLength = $container->getParameter('doctrine_migrations.column_length');
        assert(is_int($columnLength));
        $configuration->setMigrationsColumnLength($columnLength);
        $configuration->setMigrationsExecutedAtColumnName(self::getStringParameter($container, 'doctrine_migrations.executed_at_column_name'));
        $allOrNothing = $container->getParameter('doctrine_migrations.all_or_nothing');
        assert(is_bool($allOrNothing));
        $configuration->setAllOrNothing($allOrNothing);

        // Migrations is not register from configuration loader
        if (! ($configuration instanceof AbstractFileConfiguration)) {
            $migrationsDirectory = $configuration->getMigrationsDirectory();

            if ($migrationsDirectory !== null) {
                $configuration->registerMigrationsFromDirectory($migrationsDirectory);
            }
        }

        if ($configuration->getCustomTemplate() === null) {
            $customTemplate = $container->getParameter('doctrine_migrations.custom_template');
            assert(is_string($customTemplate) || $customTemplate === null);
            $configuration->setCustomTemplate($customTemplate);
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
     * @param Version[] $versions
     *
     * Injects the container to migrations aware of it
     */
    private static function injectContainerToMigrations(ContainerInterface $container, array $versions): void
    {
        foreach ($versions as $version) {
            $migration = $version->getMigration();
            if (! ($migration instanceof ContainerAwareInterface)) {
                continue;
            }

            $migration->setContainer($container);
        }
    }

    private static function getStringParameter(ContainerInterface $container, string $name): string
    {
        $value = $container->getParameter($name);
        assert(is_string($value));

        return $value;
    }
}
