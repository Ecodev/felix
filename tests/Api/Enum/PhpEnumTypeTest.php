<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Enum;

use Ecodev\Felix\Api\Enum\PhpEnumType;
use EcodevTests\Felix\Service\OtherTestEnum;
use EcodevTests\Felix\Service\TestEnum;
use PHPUnit\Framework\TestCase;

class PhpEnumTypeTest extends TestCase
{
    public function testLocalizedDescription(): void
    {
        $type = new PhpEnumType(TestEnum::class);
        self::assertSame('custom description for key 1', $type->getValues()[0]->description);
        self::assertSame('other for key 2', $type->getValues()[1]->description);

        $normalType = new PhpEnumType(OtherTestEnum::class);
        self::assertSame('static description via webonyx/graphql', $normalType->getValues()[0]->description, 'base features are still working');
    }
}
