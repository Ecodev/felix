<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Cake\Chronos\Date;
use Ecodev\Felix\Api\Scalar\DateType;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use PHPUnit\Framework\TestCase;

final class DateTypeTest extends TestCase
{
    /**
     * @var string
     */
    private $timezone;

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
        $type = new DateType();
        $date = new Date('2010-02-03');
        $actual = $type->serialize($date);
        self::assertSame('2010-02-03', $actual);
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseValue(string $input, string $expected): void
    {
        $type = new DateType();
        $actual = $type->parseValue($input);
        self::assertInstanceOf(Date::class, $actual);
        self::assertSame($expected, $actual->format('c'));
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseLiteral(string $input, string $expected): void
    {
        $type = new DateType();
        $ast = new StringValueNode(['value' => $input]);

        $actual = $type->parseLiteral($ast);
        self::assertInstanceOf(Date::class, $actual);
        self::assertSame($expected, $actual->format('c'));
    }

    public function testParseLiteralAsInt(): void
    {
        $type = new DateType();
        $ast = new IntValueNode(['value' => 123]);

        $this->expectExceptionMessage('Query error: Can only parse strings got: IntValue');
        $type->parseLiteral($ast);
    }

    public function providerValues(): array
    {
        return [
            'normal' => ['2010-06-09', '2010-06-09T00:00:00+02:00'],
            'time should be ignored' => ['2010-06-09T23:00:00', '2010-06-09T00:00:00+02:00'],
            'timezone should be ignored' => ['2010-06-09T02:00:00+08:00', '2010-06-09T00:00:00+02:00'],
            'unusual timezone should be ignored' => ['2020-06-24T23:30:00+04.5:0-30', '2020-06-24T00:00:00+02:00'],
        ];
    }
}
