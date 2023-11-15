<?php

declare(strict_types=1);

namespace EcodevTests\Felix\DBAL\Types;

use Cake\Chronos\ChronosTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Ecodev\Felix\DBAL\Types\TimeType;
use PHPUnit\Framework\TestCase;

class TimeTypeTest extends TestCase
{
    private TimeType $type;

    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new TimeType();
        $this->platform = new MySQLPlatform();
    }

    public function testConvertToDatabaseValue(): void
    {
        self::assertSame('TIME', $this->type->getSqlDeclaration(['foo'], $this->platform));
        self::assertFalse($this->type->requiresSQLCommentHint($this->platform));

        $actual = $this->type->convertToDatabaseValue(new ChronosTime('09:33'), $this->platform);
        self::assertSame('09:33:00', $actual, 'support Chronos');

        self::assertNull($this->type->convertToDatabaseValue(null, $this->platform), 'support null values');
    }

    public function testConvertToPHPValue(): void
    {
        $actualPhp = $this->type->convertToPHPValue('18:59:23', $this->platform);
        self::assertInstanceOf(ChronosTime::class, $actualPhp);
        self::assertSame('18:59:23', $actualPhp->__toString(), 'support string');

        $actualPhp = $this->type->convertToPHPValue(new ChronosTime('18:59:23'), $this->platform);
        self::assertInstanceOf(ChronosTime::class, $actualPhp);
        self::assertSame('18:59:23', $actualPhp->__toString(), 'support ChronosTime');

        self::assertNull($this->type->convertToPHPValue(null, $this->platform), 'support null values');
    }

    public function testConvertToPHPValueThrowsWithInvalidValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToPHPValue(123, $this->platform);
    }

    public function testConvertToDatabaseValueThrowsWithInvalidValue(): void
    {
        $this->expectException(ConversionException::class);

        $this->type->convertToDatabaseValue(123, $this->platform);
    }
}
