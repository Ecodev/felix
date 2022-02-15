<?php

declare(strict_types=1);

namespace EcodevTests\Felix;

use Ecodev\Felix\Format;
use PHPUnit\Framework\TestCase;

final class FormatTest extends TestCase
{
    public function truncateProvider(): array
    {
        return [
            [['abcdef', 100], 'abcdef'],
            [['abcdef', 6], 'abcdef'],
            [['abcdef', 3], 'ab…'],
            [['abcdef', 3, ''], 'abc'],
            [['abcdefghi', 5, 'foo'], 'abfoo'],
        ];
    }

    /**
     * @dataProvider truncateProvider
     */
    public function testTruncate(array $args, string $expected): void
    {
        $actual = Format::truncate(...$args);
        self::assertSame($expected, $actual);
    }
}
