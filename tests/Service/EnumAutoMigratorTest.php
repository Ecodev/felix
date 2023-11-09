<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Ecodev\Felix\DBAL\Types\EnumType;
use Ecodev\Felix\DBAL\Types\PhpEnumType;
use Ecodev\Felix\Service\EnumAutoMigrator;
use PHPUnit\Framework\TestCase;

class EnumAutoMigratorTest extends TestCase
{
    public function testPostGenerateSchema(): void
    {
        $enumAutoMigrator = new EnumAutoMigrator();
        self::assertIsCallable([$enumAutoMigrator, ToolEvents::postGenerateSchema], 'must have method that can be called by Doctrine');

        $enumType1 = new class() extends EnumType {
            protected function getPossibleValues(): array
            {
                return ['key1' => 'val1'];
            }
        };

        $enumType2 = new class() extends EnumType {
            protected function getPossibleValues(): array
            {
                return ['key1' => 'val1'];
            }
        };

        $phpEnumType = new class() extends PhpEnumType {
            protected function getEnumType(): string
            {
                return TestEnum::class;
            }
        };

        $col1 = new Column('col1', new StringType());
        $col2 = new Column('col2', $enumType1);
        $col3 = new Column('col3', $enumType2);
        $col3bis = new Column('col3bis', $enumType2);
        $col4 = new Column('col4', $phpEnumType);

        $event = new GenerateSchemaEventArgs(
            $this->createMock(EntityManager::class),
            new Schema([
                new Table('foo', [$col1, $col2, $col3, $col3bis, $col4]),
            ])
        );

        $enumAutoMigrator->postGenerateSchema($event);

        self::assertNull($col1->getComment());
        self::assertSame('(FelixEnum:59be1fe78104fed1c6b2e6aada4faf62)', $col2->getComment());
        self::assertSame($col2->getComment(), $col3->getComment(), 'different enum that happen to have same definition have same hash, because it makes no difference for DB');
        self::assertSame('(FelixEnum:59be1fe78104fed1c6b2e6aada4faf62)', $col3bis->getComment(), 'different column with exact same type must also have same hash');
        self::assertSame('(FelixEnum:fa38e8669a8a21493a62a0d493a28ad0)', $col4->getComment(), 'native PHP enum are supported too');
    }
}
