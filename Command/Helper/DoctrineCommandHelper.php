<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Command\Helper;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper as BaseDoctrineCommandHelper;
use Doctrine\DBAL\Sharding\PoolingShardConnection;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;

use function assert;
use function count;
use function is_string;
use function sprintf;

/**
 * Provides some helper and convenience methods to configure doctrine commands in the context of bundles
 * and multiple connections/entity managers.
 */
abstract class DoctrineCommandHelper extends BaseDoctrineCommandHelper
{
    public static function setApplicationHelper(Application $application, InputInterface $input): void
    {
        $container = $application->getKernel()->getContainer();
        $doctrine  = $container->get('doctrine');
        assert($doctrine instanceof ManagerRegistry);

        $managerNames = $doctrine->getManagerNames();

        if ($input->getOption('db') !== null || count($managerNames) === 0) {
            self::setApplicationConnection($application, $input->getOption('db'));
        } else {
            self::setApplicationEntityManager($application, $input->getOption('em'));
        }

        if ($input->getOption('shard') === null) {
            return;
        }

        $dbHelper = $application->getHelperSet()->get('db');
        assert($dbHelper instanceof ConnectionHelper);

        $connection = $dbHelper->getConnection();

        if (! $connection instanceof PoolingShardConnection) {
            if (count($managerNames) === 0) {
                $db = $input->getOption('db');
                assert(is_string($db));

                throw new LogicException(sprintf(
                    "Connection '%s' must implement shards configuration.",
                    $db
                ));
            }

            $em = $input->getOption('em');
            assert(is_string($em));

            throw new LogicException(sprintf(
                "Connection of EntityManager '%s' must implement shards configuration.",
                $em
            ));
        }

        $shard = $input->getOption('shard');
        assert(is_string($shard));

        $connection->connect($shard);
    }
}
