<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Cake\Chronos\Chronos;
use Ecodev\Felix\Api\Scalar\ChronosType;
use GraphQL\Error\Error;
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
     * @dataProvider providerValues
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
     * @dataProvider providerValues
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

    public static function providerValues(): iterable
    {
        return [
            'UTC' => ['2018-09-14T22:00:00.000Z', '2018-09-15T00:00:00+02:00'],
            'local time' => ['2018-09-15T00:00:00+02:00', '2018-09-15T00:00:00+02:00'],
            'other time' => ['2018-09-15T02:00:00+04:00', '2018-09-15T00:00:00+02:00'],
            'empty string' => ['', null],
        ];
    }

    public function testParseLiteralAsInt(): void
    {
        $type = new ChronosType();
        $ast = new IntValueNode(['value' => '123']);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Query error: Can only parse strings got: IntValue');
        $type->parseLiteral($ast);
    }

    public function testParseValueAsInt(): void
    {
        $type = new ChronosType();

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cannot represent value as Chronos date: 123');
        $type->parseValue(123);
    }
}
