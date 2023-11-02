<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\LoginType;

final class LoginTypeTest extends AbstractStringBasedType
{
    public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType
    {
        return new LoginType();
    }

    public function getTypeName(): string
    {
        return 'Login';
    }

    public static function providerValues(): iterable
    {
        return [
            ['', '', false],
            ['a', 'a', true],
            ['A', 'A', true],
            ['aA123.-_', 'aA123.-_', true],
            [' ', ' ', false],
            ['a ', 'a ', false],
        ];
    }
}
