<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use Money\Money;

final class CHFType extends AbstractMoneyType
{
    public ?string $description = 'A CHF money amount.';

    /**
     * @param numeric-string $value
     */
    protected function createMoney(string $value): Money
    {
        return Money::CHF($value);
    }
}
