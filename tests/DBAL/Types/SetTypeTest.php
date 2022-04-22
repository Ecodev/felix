<?php

declare(strict_types=1);

namespace EcodevTests\Felix\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Ecodev\Felix\DBAL\Types\SetType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SetTypeTest extends TestCase
{
    private SetType $type;

    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new class() extends SetType {
            protected function getPossibleValues(): array
            {
                return ['value1', 'value2'];
            }
        };

        $this->platform = new MySQLPlatform();
    }

    public function testSet(): void
    {
        self::assertSame("SET('value1', 'value2')", $this->type->getSqlDeclaration(['foo'], $this->platform));

        // Should always return string
        self::assertSame(['value1', 'value2'], $this->type->convertToPHPValue('value1,value2', $this->platform));

        // Should support null values or empty string
        self::assertNull($this->type->convertToPHPValue(null, $this->platform));
        self::assertSame([], $this->type->convertToPHPValue('', $this->platform));
        self::assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        self::assertSame('', $this->type->convertToDatabaseValue([], $this->platform));

        self::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    public function testConvertToPHPValueThrowsWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToPHPValue('foo', $this->platform);
    }

    public function testConvertToDatabaseValueThrowsWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToDatabaseValue(['foo'], $this->platform);
    }

    public function testConvertToDatabaseValueThrowsWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToDatabaseValue('foo', $this->platform);
    }

    public function testConvertToPHPValueThrowsWithZero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToPHPValue(0, $this->platform);
    }

    public function testConvertToDatabaseValueThrowsWithZero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToDatabaseValue(0, $this->platform);
    }

    public function testNameDependsOnValues(): void
    {
        $class = new ReflectionClass($this->type);
        $shortClassName = $class->getShortName();

        self::assertSame($shortClassName, $this->type->getName());
    }
}
