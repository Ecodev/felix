<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

final class ChronosType extends DateTimeType
{
    /**
     * @param null|DateTimeInterface|int|string $value
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof Chronos) {
            return $value;
        }

        $val = new Chronos($value);

        return $val;
    }
}
