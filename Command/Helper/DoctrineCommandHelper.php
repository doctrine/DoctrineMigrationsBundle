<?php

namespace Doctrine\Bundle\MigrationsBundle\Command\Helper;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper as BaseDoctrineCommandHelper;
use Doctrine\DBAL\Sharding\PoolingShardConnection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides some helper and convenience methods to configure doctrine commands in the context of bundles
 * and multiple connections/entity managers.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class DoctrineCommandHelper extends BaseDoctrineCommandHelper
{
    public static function setApplicationHelper(Application $application, InputInterface $input)
    {
        $container = $application->getKernel()->getContainer();
        $doctrine  = $container->get('doctrine');
        $managerNames = $doctrine->getManagerNames();

        if ($input->getOption('db') || empty($managerNames)) {
            self::setApplicationConnection($application, $input->getOption('db'));
        } else {
            self::setApplicationEntityManager($application, $input->getOption('em'));
        }

        if ($input->getOption('shard')) {
            $connection = $application->getHelperSet()->get('db')->getConnection();
            if (!$connection instanceof PoolingShardConnection) {
                if (empty($managerNames)) {
                    throw new \LogicException(sprintf("Connection '%s' must implement shards configuration.", $input->getOption('db')));
                } else {
                    throw new \LogicException(sprintf("Connection of EntityManager '%s' must implement shards configuration.", $input->getOption('em')));
                }
            }

            $connection->connect($input->getOption('shard'));
        }
    }
}
