<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Cake\Chronos\ChronosTime;
use Ecodev\Felix\Api\Scalar\TimeType;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use PHPUnit\Framework\TestCase;

final class TimeTypeTest extends TestCase
{
    public function testSerialize(): void
    {
        $type = new TimeType();
        $time = new ChronosTime('14:30:25');
        $actual = $type->serialize($time);
        self::assertSame('14:30:25.000000', $actual);

        // Test serialize with microseconds
        $time = new ChronosTime('23:59:59.1254');
        $actual = $type->serialize($time);
        self::assertSame('23:59:59.001254', $actual);
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseLiteral(string $input, ?string $expected): void
    {
        $type = new TimeType();
        $ast = new StringValueNode(['value' => $input]);

        $actual = $type->parseLiteral($ast);
        self::assertInstanceOf(ChronosTime::class, $actual);
        self::assertSame($expected, $actual->format('H:i:s.u'));
    }

    public function testParseLiteralAsInt(): void
    {
        $type = new TimeType();
        $ast = new IntValueNode(['value' => '123']);

        $this->expectExceptionMessage('Query error: Can only parse strings got: IntValue');
        $type->parseLiteral($ast);
    }

    public static function providerValues(): array
    {
        return [
            'normal timr' => ['14:30:25', '14:30:25.000000'],
            'time with milliseconds' => ['23:45:13.300', '23:45:13.000300'],
        ];
    }
}
