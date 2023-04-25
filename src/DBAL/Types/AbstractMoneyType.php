<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;
use InvalidArgumentException;
use Money\Money;

abstract class AbstractMoneyType extends IntegerType
{
    abstract protected function createMoney(string $value): Money;

    public function getName(): string
    {
        return 'Money';
    }

    /**
     * @param null|float|int|string $value
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Money
    {
        if ($value === null) {
            return null;
        }

        $val = $this->createMoney((string) $value);

        return $val;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof Money) {
            return $value->getAmount();
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('Cannot convert to database value: ' . var_export($value, true));
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
