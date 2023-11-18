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
        self::assertSame('14:30', $actual);

        // Test serialize with microseconds
        $time = new ChronosTime('23:59:59.1254');
        $actual = $type->serialize($time);
        self::assertSame('23:59', $actual);
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseValue(string $input, ?string $expected): void
    {
        $type = new TimeType();
        $actual = $type->parseValue($input);
        if ($actual) {
            $actual = $actual->__toString();
        }

        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseLiteral(string $input, ?string $expected): void
    {
        $type = new TimeType();
        $ast = new StringValueNode(['value' => $input]);

        $actual = $type->parseLiteral($ast);
        if ($actual) {
            $actual = $actual->__toString();
        }

        self::assertSame($expected, $actual);
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
            'empty string' => ['', null],
            'normal time' => ['14:30', '14:30:00'],
            'alternative separator' => ['14h30', '14:30:00'],
            'only hour' => ['14h', '14:00:00'],
            'only hour alternative' => ['14:', '14:00:00'],
            'even shorter' => ['9', '09:00:00'],
            'spaces are fines' => ['  14h00  ', '14:00:00'],
            'a bit weird, but why not' => ['  14h6  ', '14:06:00'],
        ];
    }
}
