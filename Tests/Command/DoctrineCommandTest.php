<?php

namespace Doctrine\Bundle\MigrationsBundle\Tests\DependencyInjection;

use Doctrine\Bundle\MigrationsBundle\Command\DoctrineCommand;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DoctrineCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigureMigrations()
    {
        $configurationMock = $this->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->disableOriginalConstructor()
            ->getMock();

        $configurationMock->method('getMigrations')
            ->willReturn(array());

        $reflectionClass = new ReflectionClass('Doctrine\DBAL\Migrations\Configuration\Configuration');
        if ($reflectionClass->hasMethod('getCustomTemplate')) {
            $configurationMock
                ->expects($this->once())
                ->method('setCustomTemplate')
                ->with('migrations.tpl');
        }

        $configurationMock
            ->expects($this->once())
            ->method('setMigrationsTableName')
            ->with('migrations');

        $configurationMock
            ->expects($this->once())
            ->method('setMigrationsNamespace')
            ->with('App\Migrations');

        $configurationMock
            ->expects($this->once())
            ->method('setMigrationsDirectory')
            ->with(__DIR__ . '/../../');

        DoctrineCommand::configureMigrations($this->getContainer(), $configurationMock);
    }

    private function getContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'doctrine_migrations.dir_name' => __DIR__ . '/../../',
            'doctrine_migrations.namespace' => 'App\\Migrations',
            'doctrine_migrations.name' => 'App migrations',
            'doctrine_migrations.table_name' => 'migrations',
            'doctrine_migrations.organize_migrations' => Configuration::VERSIONS_ORGANIZATION_BY_YEAR,
            'doctrine_migrations.custom_template' => 'migrations.tpl',
        )));
    }
}
