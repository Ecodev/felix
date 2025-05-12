<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\EventListener;

use Doctrine\Migrations\Configuration\Migration\ConfigurationLoader;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

final class HideMigrationStorageFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): HideMigrationStorage
    {
        /** @var ConfigurationLoader $configurationLoader */
        $configurationLoader = $container->get(ConfigurationLoader::class);
        $storageConfiguration = $configurationLoader->getConfiguration()->getMetadataStorageConfiguration();

        $tableName = $storageConfiguration instanceof TableMetadataStorageConfiguration ? $storageConfiguration->getTableName() : null;

        return new HideMigrationStorage($tableName);
    }
}
