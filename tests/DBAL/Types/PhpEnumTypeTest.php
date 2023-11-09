<?php

declare(strict_types=1);

namespace EcodevTests\Felix\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Ecodev\Felix\DBAL\Types\PhpEnumType;
use EcodevTests\Felix\Service\OtherTestEnum;
use EcodevTests\Felix\Service\TestEnum;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ValueError;

class PhpEnumTypeTest extends TestCase
{
    private PhpEnumType $type;

    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new class() extends PhpEnumType {
            protected function getEnumType(): string
            {
                return TestEnum::class;
            }
        };

        $this->platform = new MySQLPlatform();
    }

    public function testEnum(): void
    {
        self::assertSame("ENUM('value1', 'value2')", $this->type->getSqlDeclaration(['foo'], $this->platform));

        // Should always return string
        self::assertSame(TestEnum::key1, $this->type->convertToPHPValue('value1', $this->platform));

        // Should support null values or empty string
        self::assertNull($this->type->convertToPHPValue(null, $this->platform));
        self::assertNull($this->type->convertToPHPValue('', $this->platform));
        self::assertNull($this->type->convertToDatabaseValue(null, $this->platform));

        self::assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }

    public function testConvertToPHPValueThrowsWithInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        $this->type->convertToPHPValue('foo', $this->platform);
    }

    public function testConvertToDatabaseValueThrowsWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToDatabaseValue('foo', $this->platform);
    }

    public function testConvertToDatabaseValueThrowsWithInvalidEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToDatabaseValue(OtherTestEnum::key1, $this->platform);
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
}
