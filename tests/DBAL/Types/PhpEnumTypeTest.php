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
        self::assertSame("ENUM('value1', 'value2')", $this->type->getSqlDeclaration(['foo' => 'bar'], $this->platform));
    }

    /**
     * @dataProvider providerConvertToPHPValue
     */
    public function testConvertToPHPValue(?string $input, ?TestEnum $expected): void
    {
        self::assertSame($expected, $this->type->convertToPHPValue($input, $this->platform));
    }

    public function providerConvertToPHPValue(): iterable
    {
        yield ['value1', TestEnum::key1];
        yield [null, null];
        yield ['', null];
    }

    /**
     * @dataProvider  providerConvertToDatabaseValue
     */
    public function testConvertToDatabaseValue(mixed $input, ?string $expected): void
    {
        self::assertSame($expected, $this->type->convertToDatabaseValue($input, $this->platform));
    }

    public function providerConvertToDatabaseValue(): iterable
    {
        yield [null, null];
        yield [TestEnum::key1, 'value1'];
        yield ['value1', 'value1'];
    }

    /**
     * @dataProvider  providerInvalidConvertToDatabaseValue
     */
    public function testInvalidConvertToDatabaseValue(mixed $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToDatabaseValue($input, $this->platform);
    }

    public function providerInvalidConvertToDatabaseValue(): iterable
    {
        yield ['foo'];
        yield ['key1'];
        yield [OtherTestEnum::key1];
        yield [0];
    }

    public function testConvertToPHPValueThrowsWithInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        $this->type->convertToPHPValue('foo', $this->platform);
    }

    public function testConvertToPHPValueThrowsWithZero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToPHPValue(0, $this->platform);
    }
}
