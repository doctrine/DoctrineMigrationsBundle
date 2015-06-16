<?php

/*
 * This file is part of the Doctrine MigrationsBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Guilehrme Blanco <guilhermeblanco@hotmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MigrationsBundle\Command\Helper;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper as BaseDoctrineCommandHelper;
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
        $doctrine  = $application->getKernel()->getContainer()->get('doctrine');
        $managerNames = $doctrine->getManagerNames();

        if (empty($managerNames)) {
            self::setApplicationConnection($application, $input->getOption('db'));
        } else {
            self::setApplicationEntityManager($application, $input->getOption('em'));
        }
    }
}
