<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

final class ChronosType extends DateTimeType
{
    /**
     * @return ($value is null ? null : string)
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($platform->getDateTimeFormatString());
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', 'Chronos']);
    }

    /**
     * @return ($value is null ? null : Chronos)
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Chronos
    {
        if ($value === null || $value instanceof Chronos) {
            return $value;
        }

        if (!is_string($value) && !$value instanceof DateTimeInterface) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString(),
            );
        }

        $val = new Chronos($value);

        return $val;
    }
}
