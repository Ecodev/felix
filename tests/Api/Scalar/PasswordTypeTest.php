<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\PasswordType;

final class PasswordTypeTest extends AbstractStringBasedType
{
    public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType
    {
        return new PasswordType();
    }

    public function getTypeName(): string
    {
        return 'Password';
    }

    public static function providerValues(): iterable
    {
        return [
            ['', '', false],
            ['a', 'a', false],
            ['aA123.-_', 'aA123.-_', false],
            [str_repeat('a', 12), str_repeat('a', 12), true],
            [str_repeat('/.,?><\';[]\\|{}+_)(*&^%$#@!`123', 100), str_repeat('/.,?><\';[]\\|{}+_)(*&^%$#@!`123', 100), true],
            [null, null, false],
            [' ', ' ', false],
            ['a ', 'a ', false],
        ];
    }
}
