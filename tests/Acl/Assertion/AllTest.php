<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Acl\Assertion;

use Ecodev\Felix\Acl\Acl;
use Ecodev\Felix\Acl\Assertion\All;
use Ecodev\Felix\Acl\Assertion\NamedAssertion;
use EcodevTests\Felix\Traits\TestWithContainer;
use PHPUnit\Framework\TestCase;

class AllTest extends TestCase
{
    use TestWithContainer;

    protected function setUp(): void
    {
        $this->createDefaultFelixContainer();
    }

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

        $assertion = new All(...$assertions);

        $acl = $this->createMock(Acl::class);
        self::assertSame($expected, $assertion->assert($acl));
    }

    public function providerAssert(): array
    {
        return [
            [[], true],
            [[true], true],
            [[true, true], true],
            [[true, false], false],
            [[false, true], false],
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

        $assert = new All($assert1, $assert2);
        self::assertSame('assert1, et assert2', $assert->getName());
    }
}
