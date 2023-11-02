<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\NonUniqueEmailType;

final class NonUniqueEmailTypeTest extends AbstractStringBasedType
{
    public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType
    {
        return new NonUniqueEmailType();
    }

    public function getTypeName(): string
    {
        return 'NonUniqueEmail';
    }

    public static function providerValues(): iterable
    {
        return [
            ['john@example.com', 'john@example.com', true],
            ['josé@example.com', 'josé@example.com', false],
            ['john@example.non-existing-tld', 'john@example.non-existing-tld', false],
            ['root@localhost', 'root@localhost', false],
            ['foo', 'foo', false],
            ['', '', true],
        ];
    }
}
