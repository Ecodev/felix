<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\EventListener;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Acts as a console command event dispatcher, and a schema filter, that hides the migration
 * metadata table when the command executed is comparing actual DB structure and what it should be.
 */
final class HideMigrationStorage implements EventDispatcherInterface
{
    private bool $enabled = false;

    public function __construct(private readonly ?string $configurationTableName)
    {
    }

    public function __invoke(AbstractAsset|string $asset): bool
    {
        if (!$this->enabled) {
            return true;
        }

        if ($asset instanceof AbstractAsset) {
            $asset = $asset->getName();
        }

        return $asset !== $this->configurationTableName;
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        if (
            $this->configurationTableName
            && $event instanceof ConsoleCommandEvent
            && (
                $event->getCommand() instanceof ValidateSchemaCommand
                || $event->getCommand() instanceof UpdateCommand
            )) {
            $this->enabled = true;
        }

        return $event;
    }
}
