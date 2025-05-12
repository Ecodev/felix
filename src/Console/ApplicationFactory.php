<?php

declare(strict_types=1);

namespace Ecodev\Felix\Console;

use Composer\InstalledVersions;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\CurrentCommand;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\DumpSchemaCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\ListCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\RollupCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\SyncMetadataCommand;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Ecodev\Felix\DBAL\EventListener\HideMigrationStorage;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;

final class ApplicationFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Application
    {
        /** @var DependencyFactory $dependencyFactory */
        $dependencyFactory = $container->get(DependencyFactory::class);

        /** @var HideMigrationStorage $dispatcher */
        $dispatcher = $container->get(HideMigrationStorage::class);

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        $entityManagerProvider = new SingleManagerProvider($entityManager);

        $commands = [
            new CurrentCommand($dependencyFactory),
            new DumpSchemaCommand($dependencyFactory),
            new ExecuteCommand($dependencyFactory),
            new GenerateCommand($dependencyFactory),
            new LatestCommand($dependencyFactory),
            new MigrateCommand($dependencyFactory),
            new RollupCommand($dependencyFactory),
            new StatusCommand($dependencyFactory),
            new VersionCommand($dependencyFactory),
            new UpToDateCommand($dependencyFactory),
            new SyncMetadataCommand($dependencyFactory),
            new ListCommand($dependencyFactory),
            new DiffCommand($dependencyFactory),
        ];

        $version = InstalledVersions::getVersion('ecodev/felix');
        assert($version !== null);

        $cli = new Application('Felix', $version);
        $cli->setDispatcher($dispatcher);
        $cli->setCatchExceptions(true);

        ConsoleRunner::addCommands($cli, $entityManagerProvider);
        $cli->addCommands($commands);

        return $cli;
    }
}
