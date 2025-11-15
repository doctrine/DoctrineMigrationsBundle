<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\Migrations;

use Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\Services\FooService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;

class ServiceMigration001 extends AbstractMigration
{
    public function __construct(Connection $connection, public FooService $fooService, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);
    }

    public function up(Schema $schema): void
    {
        // TODO: Implement up() method.
    }
}
