<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\FilesystemMigrations;

use Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\Services\FooService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;

class VersionFilesystemService001 extends AbstractMigration
{
    /** @var FooService */
    public $fooService;

    public function __construct(Connection $connection, FooService $fooService, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);

        $this->fooService = $fooService;
    }

    public function up(Schema $schema): void
    {
    }
}
