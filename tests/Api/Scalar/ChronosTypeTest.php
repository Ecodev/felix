<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Cake\Chronos\Chronos;
use Ecodev\Felix\Api\Scalar\ChronosType;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use PHPUnit\Framework\TestCase;

final class ChronosTypeTest extends TestCase
{
    private string $timezone;

    protected function setUp(): void
    {
        $this->timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Zurich');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);
    }

    public function testSerialize(): void
    {
        $type = new ChronosType();
        $date = new Chronos('2018-09-15T00:00:00+02:00');
        $actual = $type->serialize($date);
        self::assertSame('2018-09-15T00:00:00+02:00', $actual);
    }

    /**
     * @dataProvider providerValue
     */
    public function testParseValue(string $input, ?string $expected): void
    {
        $type = new ChronosType();
        $actual = $type->parseValue($input);
        if ($actual) {
            $actual = $actual->format('c');
        }

        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider providerValue
     */
    public function testParseLiteral(string $input, ?string $expected): void
    {
        $type = new ChronosType();
        $ast = new StringValueNode(['value' => $input]);

        $actual = $type->parseLiteral($ast);
        if ($actual) {
            $actual = $actual->format('c');
        }

        self::assertSame($expected, $actual);
    }

    public function testParseLiteralAsInt(): void
    {
        $type = new ChronosType();
        $ast = new IntValueNode(['value' => 123]);

        $this->expectExceptionMessage('Query error: Can only parse strings got: IntValue');
        $type->parseLiteral($ast);
    }

    public static function providerValue(): array
    {
        return [
            'UTC' => ['2018-09-14T22:00:00.000Z', '2018-09-15T00:00:00+02:00'],
            'local time' => ['2018-09-15T00:00:00+02:00', '2018-09-15T00:00:00+02:00'],
            'other time' => ['2018-09-15T02:00:00+04:00', '2018-09-15T00:00:00+02:00'],
            'empty string' => ['', null],
        ];
    }
}
