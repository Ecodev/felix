<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\ChronosDate;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

final class DateType extends \Doctrine\DBAL\Types\DateType
{
    /**
     * @return ($value is null ? null : string)
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof ChronosDate) {
            return $value->format($platform->getDateFormatString());
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', 'ChronosDate']);
    }

    /**
     * @return ($value is null ? null : ChronosDate)
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ChronosDate
    {
        if ($value === null || $value instanceof ChronosDate) {
            return $value;
        }

        if (!is_string($value)) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateFormatString(),
            );
        }

        $val = new ChronosDate($value);

        return $val;
    }
}
