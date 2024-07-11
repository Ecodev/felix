<?php

declare(strict_types=1);

namespace EcodevTests\Felix\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Ecodev\Felix\DBAL\Types\LocalizedType;
use PHPUnit\Framework\TestCase;

class LocalizedTypeTest extends TestCase
{
    private LocalizedType $type;

    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new LocalizedType();
        $this->platform = new MySQLPlatform();
    }

    public function testConvertToPHPValue(): void
    {
        self::assertSame([], $this->type->convertToPHPValue(null, $this->platform));
        self::assertSame([], $this->type->convertToPHPValue('', $this->platform));
        self::assertSame(['fr' => 'foo'], $this->type->convertToPHPValue('{"fr":"foo"}', $this->platform));
    }

    public function testConvertToDatabaseValue(): void
    {
        self::assertSame('{"fr":"foo"}', $this->type->convertToDatabaseValue(['fr' => 'foo'], $this->platform));
        self::assertSame('{}', $this->type->convertToDatabaseValue([], $this->platform), 'empty should still be valid JSON');
    }

    public function testConvertToDatabaseValueWillThrowIfNull(): void
    {
        $this->expectExceptionMessage("Could not convert PHP type 'NULL' to 'json', as an 'value must be a PHP array' error was triggered by the serialization");
        $this->type->convertToDatabaseValue(null, $this->platform);
    }

    public function testConvertToDatabaseValueWillThrowIfString(): void
    {
        $this->expectExceptionMessage("Could not convert PHP type 'string' to 'json', as an 'value must be a PHP array' error was triggered by the serialization");
        $this->type->convertToDatabaseValue('', $this->platform);
    }

    public function testConvertToPHPValueWillThrowIfNotJsonArray(): void
    {
        $this->expectExceptionMessage("Could not convert database value to 'json' as an error was triggered by the unserialization: 'value in DB is not a JSON encoded associative array'");
        $this->type->convertToPHPValue('"foo"', $this->platform);
    }

    public function testMustAlwaysStoreUnescaped(): void
    {
        $original = ['fr' => 'aéa/a💕a'];

        $actualDB = $this->type->convertToDatabaseValue($original, $this->platform);
        self::assertSame('{"fr":"aéa/a💕a"}', $actualDB, 'unicode and slashes should not be escaped');

        $actualPHP = $this->type->convertToPHPValue($actualDB, $this->platform);
        self::assertSame($original, $actualPHP, 'can be re-converted back to the exact same original');
    }
}
