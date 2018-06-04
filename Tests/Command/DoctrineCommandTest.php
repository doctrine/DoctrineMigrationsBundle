<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Tests\Command;

use Doctrine\Bundle\MigrationsBundle\Command\DoctrineCommand;
use Doctrine\Migrations\Configuration\Configuration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DoctrineCommandTest extends TestCase
{
    /** @var string */
    private $migrationsDirectory;

    protected function setUp() : void
    {
        vfsStream::setup('migrations_directory');

        $this->migrationsDirectory = vfsStream::url('migrations_directory');
    }

    public function testConfigureMigrations() : void
    {
        /** @var Configuration|MockObject $configurationMock */
        $configurationMock = $this->createMock(Configuration::class);

        $configurationMock->method('getMigrations')
            ->willReturn([]);

        $configurationMock->expects($this->once())
            ->method('setMigrationsDirectory')
            ->with($this->migrationsDirectory);

        $configurationMock->expects($this->once())
            ->method('setMigrationsNamespace')
            ->with('test');

        $configurationMock->expects($this->once())
            ->method('setName')
            ->with('test');

        $configurationMock->expects($this->once())
            ->method('setMigrationsTableName')
            ->with('test');

        $configurationMock->expects($this->once())
            ->method('setMigrationsAreOrganizedByYear')
            ->with(true);

        DoctrineCommand::configureMigrations($this->getContainer(), $configurationMock);
    }

    private function getContainer() : ContainerBuilder
    {
        return new ContainerBuilder(new ParameterBag([
            'doctrine_migrations.dir_name' => $this->migrationsDirectory,
            'doctrine_migrations.namespace' => 'test',
            'doctrine_migrations.name' => 'test',
            'doctrine_migrations.table_name' => 'test',
            'doctrine_migrations.organize_migrations' => Configuration::VERSIONS_ORGANIZATION_BY_YEAR,
            'doctrine_migrations.custom_template' => null,
        ]));
    }
}
