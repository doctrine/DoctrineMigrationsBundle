<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Command;

use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for generate migration classes by comparing your current database schema
 * to your mapping information.
 *
 */
class MigrationsDiffDoctrineCommand extends DiffCommand
{
    protected function configure() : void
    {
        parent::configure();

        $this
            ->setName('doctrine:migrations:diff')
            ->addOption('db', null, InputOption::VALUE_REQUIRED, 'The database connection to use for this command.')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.')
        ;
    }

    public function initialize(InputInterface $input, OutputInterface $output) : void
    {
        /** @var Application $application */
        $application = $this->getApplication();

        Helper\DoctrineCommandHelper::setApplicationHelper($application, $input);

        $configuration = $this->getMigrationConfiguration($input, $output);
        DoctrineCommand::configureMigrations($application->getKernel()->getContainer(), $configuration);

        parent::initialize($input, $output);
    }
}
