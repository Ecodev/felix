<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\ChronosDate;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;

final class DateType extends \Doctrine\DBAL\Types\DateType
{
    /**
     * @param null|ChronosDate|DateTimeInterface|string $value
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ChronosDate
    {
        if ($value === null || $value instanceof ChronosDate) {
            return $value;
        }

        $val = new ChronosDate($value);

        return $val;
    }
}
