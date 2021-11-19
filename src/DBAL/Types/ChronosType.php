<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;

final class ChronosType extends \Doctrine\DBAL\Types\DateTimeType
{
    /**
     * @param null|DateTimeInterface|int|string $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof Chronos) {
            return $value;
        }

        $val = new Chronos($value);

        return $val;
    }
}
