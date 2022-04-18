<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Acl\Assertion;

use Ecodev\Felix\Acl\Acl;
use Ecodev\Felix\Acl\Assertion\NamedAssertion;
use Ecodev\Felix\Acl\Assertion\One;
use PHPUnit\Framework\TestCase;

class OneTest extends TestCase
{
    /**
     * @dataProvider providerAssert
     */
    public function testAssert(array $input, bool $expected): void
    {
        $assertions = [];
        foreach ($input as $value) {
            $internalAssertion = $this->createMock(NamedAssertion::class);
            $internalAssertion->expects(self::atMost(1))
                ->method('assert')
                ->willReturn($value);

            $assertions[] = $internalAssertion;
        }

        $assertion = new One(...$assertions);

        $acl = $this->createMock(Acl::class);
        self::assertSame($expected, $assertion->assert($acl));
    }

    public function providerAssert(): array
    {
        return [
            [[], false],
            [[true], true],
            [[true, true], true],
            [[true, false], true],
            [[false, true], true],
            [[false, false], false],
            [[false], false],
        ];
    }

    public function testGetName(): void
    {
        $assert1 = $this->createMock(NamedAssertion::class);
        $assert1->expects(self::once())
            ->method('getName')
            ->willReturn('assert1');

        $assert2 = $this->createMock(NamedAssertion::class);
        $assert2->expects(self::once())
            ->method('getName')
            ->willReturn('assert2');

        $assert = new One($assert1, $assert2);
        self::assertSame('assert1, ou assert2', $assert->getName());
    }
}
