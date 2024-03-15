<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\EventListener;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Acts as a schema filter able that hides the migration metadata table except
 * when the execution context is that of command inside the migrations
 * namespace.
 */
final class SchemaFilterListener
{
    /** @var bool */
    private $enabled = true;

    /** @param AbstractAsset|string $asset */
    public function __invoke($asset): bool
    {
        if (! $this->enabled) {
            return true;
        }

        if ($asset instanceof AbstractAsset) {
            $asset = $asset->getName();
        }

        return $asset !== (new TableMetadataStorageConfiguration())->getTableName();
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (! $command instanceof DoctrineCommand) {
            return;
        }

        $this->disable();
    }
}
