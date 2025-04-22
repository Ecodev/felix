<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Money\Money;

abstract class AbstractMoneyType extends Type
{
    abstract protected function createMoney(string $value): Money;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
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
}
