<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\ThirdPartyMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * A migration which is not registered as a service, the way the migrations shipped by third party bundles are.
 */
class Version20240101000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
    }
}
