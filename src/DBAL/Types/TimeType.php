<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\ChronosTime;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;

final class TimeType extends \Doctrine\DBAL\Types\TimeType
{
    /**
     * @param null|ChronosTime|DateTimeInterface|string $value
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ChronosTime
    {
        if ($value === null || $value instanceof ChronosTime) {
            return $value;
        }

        $val = new ChronosTime($value);

        return $val;
    }
}
