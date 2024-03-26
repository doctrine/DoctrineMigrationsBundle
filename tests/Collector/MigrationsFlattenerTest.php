<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Collector;

use DateTimeImmutable;
use Doctrine\Bundle\MigrationsBundle\Collector\MigrationsFlattener;
use Doctrine\Bundle\MigrationsBundle\Tests\Fixtures\Migrations\Migration001;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

use function dirname;

class MigrationsFlattenerTest extends TestCase
{
    /** @var MigrationsFlattener */
    private $flattener;

    protected function setUp(): void
    {
        $this->flattener = new MigrationsFlattener();
    }

    public function testFlattenAvailableMigrations(): void
    {
        $expected = [
            [
                'version' => '012345',
                'is_new' => true,
                'is_unavailable' => false,
                'description' => '',
                'executed_at' => null,
                'execution_time' => null,
                'file' => dirname(__DIR__) . '/Fixtures/Migrations/Migration001.php',
            ],
            [
                'version' => '123456',
                'is_new' => true,
                'is_unavailable' => false,
                'description' => '',
                'executed_at' => null,
                'execution_time' => null,
                'file' => dirname(__DIR__) . '/Fixtures/Migrations/Migration001.php',
            ],
            [
                'version' => '456789',
                'is_new' => true,
                'is_unavailable' => false,
                'description' => '',
                'executed_at' => null,
                'execution_time' => null,
                'file' => dirname(__DIR__) . '/Fixtures/Migrations/Migration001.php',
            ],
        ];

        $actual = $this->flattener->flattenAvailableMigrations($this->createAvailableMigrations());
        self::assertEquals($expected, $actual);
    }

    public function testFlattenExecutedMigrations(): void
    {
        $expected = [
            [
                'version' => '012345',
                'is_new' => false,
                'is_unavailable' => false,
                'description' => '',
                'executed_at' => new DateTimeImmutable('2020-12-12 20:15:00'),
                'execution_time' => 3.2,
                'file' => dirname(__DIR__) . '/Fixtures/Migrations/Migration001.php',
            ],
            [
                'version' => '111111',
                'is_new' => false,
                'is_unavailable' => true,
                'description' => '',
                'executed_at' => new DateTimeImmutable('2020-12-14 20:30:00'),
                'execution_time' => 8.9,
                'file' => null,
            ],
        ];

        $actual = $this->flattener->flattenExecutedMigrations($this->createExecutedMigrations(), $this->createAvailableMigrations());
        self::assertEquals($expected, $actual);
    }

    private function createAvailableMigrations(): AvailableMigrationsList
    {
        $migration = new Migration001($this->createMock(Connection::class), new NullLogger());

        return new AvailableMigrationsList([
            new AvailableMigration(new Version('012345'), $migration),
            new AvailableMigration(new Version('123456'), $migration),
            new AvailableMigration(new Version('456789'), $migration),
        ]);
    }

    private function createExecutedMigrations(): ExecutedMigrationsList
    {
        return new ExecutedMigrationsList([
            new ExecutedMigration(new Version('012345'), new DateTimeImmutable('2020-12-12 20:15:00'), 3.2),
            new ExecutedMigration(new Version('111111'), new DateTimeImmutable('2020-12-14 20:30:00'), 8.9),
        ]);
    }
}
