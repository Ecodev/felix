<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\TokenType;

final class TokenTypeTest extends AbstractStringBasedType
{
    public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType
    {
        return new TokenType();
    }

    public function getTypeName(): string
    {
        return 'Token';
    }

    public static function providerValues(): iterable
    {
        return [
            ['', '', false],
            ['a', 'a', false],
            ['A', 'A', false],
            [str_repeat('z', 32), str_repeat('z', 32), true],
            [str_repeat('a', 32), str_repeat('a', 32), true],
            ['abcdefabcdef01234567890123456789', 'abcdefabcdef01234567890123456789', true],
            ['Abcdefabcdef01234567890123456789', 'Abcdefabcdef01234567890123456789', false],
            ['abcdef', 'abcdef', false],
            ['123456', '123456', true],
            ['1234567', '1234567', false],
            ['12345', '12345', false],
            [' ', ' ', false],
            ['a ', 'a ', false],
        ];
    }
}
