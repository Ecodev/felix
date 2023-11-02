<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use Money\Money;

final class EURType extends AbstractMoneyType
{
    public ?string $description = 'An EUR money amount.';

    /**
     * @param numeric-string $value
     */
    protected function createMoney(string $value): Money
    {
        return Money::EUR($value);
    }
}
