<?php
declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\Migration;

interface EnvironmentAwareMigrationInterface
{
    public function setEnvironment(string $env): void;
}