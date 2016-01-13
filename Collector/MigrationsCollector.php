<?php

/*
 * This file is part of the Doctrine MigrationsBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Michael Simonson <mike@simonson.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MigrationsBundle\Collector;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class MigrationsCollector implements DataCollectorInterface
{
    /** @var  string */
    private $migrationTablename;

    /** @var  string */
    private $migrationName;

    /** @var Connection  */
    private $connection;

    /** @var  string */
    private $migrationNamespace;

    /** @var  string */
    private $migrationDirectory;

    public function __construct(Connection $connection, $migrationNamespace, $migrationDirectory, $migrationTablename, $migrationName)
    {
        $this->connection = $connection;
        $this->migrationNamespace = $migrationNamespace;
        $this->migrationDirectory = $migrationDirectory;
        $this->migrationTablename = $migrationTablename;
        $this->migrationName = $migrationName;
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request $request A Request instance
     * @param Response $response A Response instance
     * @param \Exception $exception An Exception instance
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $configuration = new Configuration($this->connection);
        $configuration->setMigrationsNamespace($this->migrationNamespace);
        $configuration->setMigrationsDirectory($this->migrationDirectory);
        $configuration->setMigrationsTableName($this->migrationTablename);
        $configuration->setName($this->migrationName);
        $configuration->registerMigrationsFromDirectory($configuration->getMigrationsDirectory());
        $migrationsStatusInfos = new MigrationStatusInfosHelper($configuration);
        $this->data = $migrationsStatusInfos->getMigrationsInfos();
        $newMigrationsList = $configuration->getMigrationsToExecute('up', $configuration->getLatestVersion());
        $this->data['newMigrationsList'] = array_map(function($migration) {
            return $migration->getVersion();
        }, $newMigrationsList);
        $this->data['executedUnavailableMigrationsList'] = array_map(function($migration) {
            return $migration->getVersion();
        }, $migrationsStatusInfos->getExecutedUnavailableMigrations());

        $this->connection = null;
        $this->migrationDirectory = null;
        $this->migrationNamespace = null;
        $this->migrationName = null;
        $this->migrationTablename = null;
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName()
    {
        return 'doctrine.migrations_collector';
    }

    public function getPreviousMigration()
    {
        return $this->data['Previous Version'];
    }

    public function getCurrentMigration()
    {
        return $this->data['Current Version'];
    }

    public function getNextMigration()
    {
        return $this->data['Next Version'];
    }

    public function getLatestMigration()
    {
        return $this->data['Latest Version'];
    }

    public function getExecutedMigrations()
    {
        return $this->data['Executed Migrations'];
    }

    public function getExecutedUnavailableMigrations()
    {
        return $this->data['Executed Unavailable Migrations'];
    }

    public function getAvailableMigrations()
    {
        return $this->data['Available Migrations'];
    }

    public function getNewMigrations()
    {
        return $this->data['New Migrations'];
    }

    public function getNewMigrationsList()
    {
        return $this->data['newMigrationsList'];
    }

    public function getExecutedUnavailableMigrationsList()
    {
        return $this->data['executedUnavailableMigrationsList'];
    }

    public function getDatabaseDriver()
    {
        return $this->data['Database Driver'];
    }
    public function getDatabaseName()
    {
        return $this->data['Database Name'];
    }
    public function getConfigurationSource()
    {
        return $this->data['Configuration Source'];
    }
    public function getVersionTableName()
    {
        return $this->data['Version Table Name'];
    }
    public function getVersionColumnName()
    {
        return $this->data['Version Column Name'];
    }
    public function getMigrationNamespace()
    {
        return $this->data['Migrations Namespace'];
    }
    public function getMigrationDirectory()
    {
        return $this->data['Migrations Directory'];
    }
}
