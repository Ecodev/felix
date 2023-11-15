<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\ChronosTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

final class TimeType extends \Doctrine\DBAL\Types\TimeType
{
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

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', 'ChronosTime']);
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
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getTimeFormatString(),
            );
        }

        $val = new ChronosTime($value);

        return $val;
    }
}
