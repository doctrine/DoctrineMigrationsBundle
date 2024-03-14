<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Collector\EventListener;

use Doctrine\Bundle\MigrationsBundle\EventListener\SchemaFilterListener;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class SchemaFilterListenerTest extends TestCase
{
    public function testItFiltersOutMigrationMetadataTableByDefault(): void
    {
        $listener = new SchemaFilterListener();

        self::assertFalse($listener(new Table('doctrine_migration_versions')));
        self::assertTrue($listener(new Table('some_other_table')));
    }

    public function testItFiltersNothingWhenDisabled(): void
    {
        $listener = new SchemaFilterListener();
        $listener->disable();

        self::assertTrue($listener(new Table('doctrine_migration_versions')));
        self::assertTrue($listener(new Table('some_other_table')));
    }

    public function testItDisablesItselfWhenTheCurrentCommandIsAMigrationsCommand(): void
    {
        $listener          = new SchemaFilterListener();
        $migrationsCommand = new class extends DoctrineCommand {
        };

        $listener->onConsoleCommand(new ConsoleCommandEvent(
            $migrationsCommand,
            new ArrayInput([]),
            new NullOutput()
        ));

        self::assertTrue($listener(new Table('doctrine_migration_versions')));
    }
}
