<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MigrationsBundle\EventListener;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\AbstractNamedObject;
use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
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
    public function __construct(private readonly string $configurationTableName)
    {
    }

    private bool $enabled = false;

    /** @param AbstractAsset<OptionallyQualifiedName>|AbstractNamedObject<OptionallyQualifiedName>|string $asset */
    public function __invoke(AbstractAsset|AbstractNamedObject|string $asset): bool
    {
        if (! $this->enabled) {
            return true;
        }

        if ($asset instanceof AbstractAsset) {
            $asset = $asset instanceof AbstractNamedObject
                ? $asset->getObjectName()->toString()
                : $asset->getName();
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
