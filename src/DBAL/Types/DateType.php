<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Cake\Chronos\Date;
use Doctrine\DBAL\Platforms\AbstractPlatform;

final class DateType extends \Doctrine\DBAL\Types\DateType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof Date) {
            return $value;
        }

        $val = new Date($value);

        return $val;
    }
}
