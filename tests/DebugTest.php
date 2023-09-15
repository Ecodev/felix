<?php

declare(strict_types=1);

namespace EcodevTests\Felix;

use Ecodev\Felix\Debug;
use PHPUnit\Framework\TestCase;

class DebugTest extends TestCase
{
    /**
     * @dataProvider providerExport
     */
    public function testExport(mixed $data, string $expected): void
    {
        $actual = Debug::export($data, true);
        self::assertEquals($expected, $actual);
    }

    public static function providerExport(): array
    {
        return [
            [123, '123'],
            ['123', "'123'"],
            [[1, 2, 3], '[
    1,
    2,
    3,
]'],
            [[1, 2, ['key1' => 'value', 'key2' => ['a', 'b']], 4], "[
    1,
    2,
    [
        'key1' => 'value',
        'key2' => [
            'a',
            'b',
        ],
    ],
    4,
]"],
            [[1 => 1, 2 => 2, 3 => 3], '[
    1 => 1,
    2 => 2,
    3 => 3,
]'],
            [[0 => 1, 3 => 2, 2 => 3], '[
    0 => 1,
    3 => 2,
    2 => 3,
]'],
        ];
    }
}
