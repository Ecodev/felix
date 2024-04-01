<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\CountryCode2Type;

final class CountryCode2TypeTest extends AbstractStringBasedType
{
    public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType
    {
        return new CountryCode2Type();
    }

    public function getTypeName(): string
    {
        return 'CountryCode2';
    }

    public static function providerValues(): iterable
    {
        return [
            ['fr', 'FR', true], // Valid lowercase
            ['', '', false], // Empty
            ['A', 'A', false], // Too short
            ['ZZ', 'ZZ', false], // Not valid
            ['CH', 'CH', true], // Valid
            ['CHF', 'CHF', false], // Too long
        ];
    }
}
