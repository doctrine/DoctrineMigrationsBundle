<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\FilesystemMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class VersionFilesystem001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
    }
}
