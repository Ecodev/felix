<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\ColorType;

final class ColorTypeTest extends AbstractStringBasedType
{
    public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType
    {
        return new ColorType();
    }

    public function getTypeName(): string
    {
        return 'Color';
    }

    public static function providerValues(): iterable
    {
        return [
            ['', '', true],
            ['#AABBCC', '#AABBCC', true],
            ['#AABBC', '#AABBC', false],
            ['#AABBCCC', '#AABBCCC', false],
            ['#01aB9F', '#01aB9F', true],
            ['#ZZZZZZ', '#ZZZZZZ', false],
            ['AABBCC', 'AABBCC', false],
            [' ', ' ', false],
        ];
    }
}
