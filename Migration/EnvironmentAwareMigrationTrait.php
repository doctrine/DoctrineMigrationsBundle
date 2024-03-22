<?php
declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Migration;

trait EnvironmentAwareMigrationTrait
{
    /** @var string */
    private $environment;

    public function setEnvironment(string $env): void
    {
        $this->environment = $env;
    }
}