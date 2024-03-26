<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\Migrations;

use Doctrine\Bundle\MigrationsBundle\Migration\EnvironmentAwareMigrationInterface;
use Doctrine\Bundle\MigrationsBundle\Migration\EnvironmentAwareMigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class EnvironmentAwareMigration extends AbstractMigration implements EnvironmentAwareMigrationInterface
{
    use EnvironmentAwareMigrationTrait;

    public function up(Schema $schema): void
    {
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }
}
