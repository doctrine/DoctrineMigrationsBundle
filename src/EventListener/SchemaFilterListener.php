<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\EventListener;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Acts as a schema filter that hides the migration metadata table except
 * when the execution context is that of command inside the migrations
 * namespace.
 *
 * @internal
 */
final class SchemaFilterListener
{
    public function __construct(private string $configurationTableName)
    {
    }

    private bool $enabled = false;

    public function __invoke(AbstractAsset|string $asset): bool
    {
        if (! $this->enabled) {
            return true;
        }

        if ($asset instanceof AbstractAsset) {
            $asset = $asset->getName();
        }

        return $asset !== $this->configurationTableName;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (! $command instanceof ValidateSchemaCommand && ! $command instanceof UpdateCommand) {
            return;
        }

        $this->enabled = true;
    }
}
