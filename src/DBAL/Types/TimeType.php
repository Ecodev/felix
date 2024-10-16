<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\ChronosTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;

final class TimeType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getTimeTypeDeclarationSQL($column);
    }

    /**
     * @return ($value is null ? null : string)
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof ChronosTime) {
            return $value->format($platform->getTimeFormatString());
        }

        throw InvalidType::new($value, self::class, ['null', 'ChronosTime']);
    }

    /**
     * @return ($value is null ? null : ChronosTime)
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ChronosTime
    {
        if ($value === null || $value instanceof ChronosTime) {
            return $value;
        }

        if (!is_string($value)) {
            throw InvalidFormat::new(
                (string) $value,
                self::class,
                $platform->getTimeFormatString(),
            );
        }

        $val = new ChronosTime($value);

        return $val;
    }
}
