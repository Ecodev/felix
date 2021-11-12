<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Money\Money;

final class CHFType extends AbstractMoneyType
{
    public function getName(): string
    {
        return 'CHF';
    }

    protected function createMoney(string $value): Money
    {
        return Money::CHF($value);
    }
}
