<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Collector\EventListener;

use Doctrine\Bundle\MigrationsBundle\EventListener\SchemaFilterListener;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Doctrine\ORM\Tools\Console\Command\AbstractEntityManagerCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class SchemaFilterListenerTest extends TestCase
{
    public function testItFiltersNothingByDefault(): void
    {
        $listener = new SchemaFilterListener('doctrine_migration_versions');
        self::assertTrue($listener(new Table('doctrine_migration_versions')));
        self::assertTrue($listener(new Table('some_other_table')));
    }

    public function testItFiltersNothingWhenNotRunningSpecificCommands(): void
    {
        $listener          = new SchemaFilterListener('doctrine_migration_versions');
        $migrationsCommand = new class () extends DoctrineCommand {
        };

        $listener->onConsoleCommand(new ConsoleCommandEvent(
            $migrationsCommand,
            new ArrayInput([]),
            new NullOutput()
        ));

        self::assertTrue($listener(new Table('doctrine_migration_versions')));
        self::assertTrue($listener(new Table('some_other_table')));
    }

    /**
     * @param class-string<AbstractEntityManagerCommand> $command
     *
     * @dataProvider getCommands
     */
    public function testItFiltersOutMigrationMetadataTableWhenRunningSpecificCommands(string $command): void
    {
        $listener   = new SchemaFilterListener('doctrine_migration_versions');
        $ormCommand = new $command($this->createStub(EntityManagerProvider::class));

        $listener->onConsoleCommand(new ConsoleCommandEvent(
            $ormCommand,
            new ArrayInput([]),
            new NullOutput()
        ));

        self::assertFalse($listener(new Table('doctrine_migration_versions')));
        self::assertTrue($listener(new Table('some_other_table')));
    }

    /** @return iterable<string, array{class-string<AbstractEntityManagerCommand>}> */
    public static function getCommands(): iterable
    {
        yield 'orm:validate-schema' => [ValidateSchemaCommand::class];
        yield 'orm:schema-tool:update' => [UpdateCommand::class];
    }
}
