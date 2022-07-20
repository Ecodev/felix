<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\OtpType;

final class OtpTypeTest extends AbstractStringBasedType
{
    public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType
    {
        return new OtpType();
    }

    public function getTypeName(): string
    {
        return 'Otp';
    }

    public function providerValues(): iterable
    {
        return [
            ['', '', false],
            ['3', '3', false],
            ['1234', '1234', true],
            ['949358', '949358', true],
            [null, null, false],
            [' ', ' ', false],
            ['123456789', '123456789', false],
        ];
    }
}
