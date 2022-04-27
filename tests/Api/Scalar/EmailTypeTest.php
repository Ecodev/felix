<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\EmailType;

final class EmailTypeTest extends AbstractStringBasedType
{
    public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType
    {
        return new EmailType();
    }

    public function getTypeName(): string
    {
        return 'Email';
    }

    public function providerValues(): iterable
    {
        return [
            ['john@example.com', 'john@example.com', true],
            ['josé@example.com', 'josé@example.com', false],
            ['john@example.non-existing-tld', 'john@example.non-existing-tld', false],
            ['john@ example.com', 'john@ example.com', false],
            ['root@localhost', 'root@localhost', false],
            ['', null, true],
            ['foo', 'foo', false],
            [null, null, true],
        ];
    }
}
