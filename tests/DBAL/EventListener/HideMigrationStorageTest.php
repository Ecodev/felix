<?php

declare(strict_types=1);

namespace EcodevTests\Felix\DBAL\EventListener;

use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Doctrine\ORM\Tools\Console\Command\AbstractEntityManagerCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Ecodev\Felix\DBAL\EventListener\HideMigrationStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class HideMigrationStorageTest extends TestCase
{
    public function testItFiltersNothingByDefault(): void
    {
        $service = new HideMigrationStorage('doctrine_migration_versions');
        self::assertTrue($service(new Table('doctrine_migration_versions')));
        self::assertTrue($service(new Table('some_other_table')));
    }

    public function testItFiltersNothingWhenNotRunningSpecificCommands(): void
    {
        $service = new HideMigrationStorage('doctrine_migration_versions');
        $migrationsCommand = new class() extends DoctrineCommand {
        };

        $service->dispatch(new ConsoleCommandEvent(
            $migrationsCommand,
            new ArrayInput([]),
            new NullOutput(),
        ));

        self::assertTrue($service(new Table('doctrine_migration_versions')));
        self::assertTrue($service(new Table('some_other_table')));
    }

    /**
     * @param class-string<AbstractEntityManagerCommand> $command
     *
     * @dataProvider providerItFiltersOutMigrationMetadataTableWhenRunningSpecificCommands
     */
    public function testItFiltersOutMigrationMetadataTableWhenRunningSpecificCommands(string $command): void
    {
        $service = new HideMigrationStorage('doctrine_migration_versions');
        $ormCommand = new $command(self::createStub(EntityManagerProvider::class));

        $service->dispatch(new ConsoleCommandEvent(
            $ormCommand,
            new ArrayInput([]),
            new NullOutput(),
        ));

        self::assertFalse($service(new Table('doctrine_migration_versions')));
        self::assertTrue($service(new Table('some_other_table')));
    }

    public static function providerItFiltersOutMigrationMetadataTableWhenRunningSpecificCommands(): iterable
    {
        yield 'orm:validate-schema' => [ValidateSchemaCommand::class];
        yield 'orm:schema-tool:update' => [UpdateCommand::class];
    }
}
