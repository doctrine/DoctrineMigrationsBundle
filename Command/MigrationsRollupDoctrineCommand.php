<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Command;

use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;

/**
 * Command for rolling up your historical migration versions and inserting the dumped schema version.
 */
class MigrationsRollupDoctrineCommand extends RollupCommand
{
    /** @var string */
    protected static $defaultName = 'doctrine:migrations:rollup';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('db', null, InputOption::VALUE_REQUIRED, 'The database connection to use for this command.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.');
    }

    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $application = $this->getApplication();
        assert($application instanceof Application);

        Helper\DoctrineCommandHelper::setApplicationHelper($application, $input);

        $configuration = $this->getMigrationConfiguration($input, $output);
        $container     = $application->getKernel()->getContainer();
        DoctrineCommand::configureMigrations($container, $configuration);

        parent::initialize($input, $output);
    }

    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        // EM and DB options cannot be set at same time
        if ($input->getOption('em') !== null && $input->getOption('db') !== null) {
            throw new InvalidArgumentException('Cannot set both "em" and "db" for command execution.');
        }

        return parent::execute($input, $output);
    }
}
