<?php

declare(strict_types=1);

namespace EcodevTests\Felix\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Ecodev\Felix\DBAL\Types\LocalizedType;
use PHPUnit\Framework\TestCase;

class LocalizedTypeTest extends TestCase
{
    /**
     * @var LocalizedType
     */
    private $type;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    protected function setUp(): void
    {
        $this->type = new LocalizedType();
        $this->platform = new MySqlPlatform();
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
        self::assertSame('', $this->type->convertToDatabaseValue([], $this->platform), 'micro-optimization of an empty array into an empty string to save two bytes');
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
}
