<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\Date;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;

final class DateType extends \Doctrine\DBAL\Types\DateType
{
    /**
     * @param null|DateTimeInterface|int|string $value
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Date
    {
        if ($value === null || $value instanceof Date) {
            return $value;
        }

        $val = new Date($value);

        return $val;
    }
}
