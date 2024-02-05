<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\CHFType;
use GraphQL\Error\Error;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use Money\Money;
use PHPUnit\Framework\TestCase;
use stdClass;

final class CHFTypeTest extends TestCase
{
    public function testSerialize(): void
    {
        $type = new CHFType();

        $money = Money::CHF('995');
        $actual = $type->serialize($money);
        self::assertSame('9.95', $actual);

        $actual = $type->serialize(995);
        self::assertSame('9.95', $actual, 'should also accept raw value when aggregated from DB');
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseValue(string $input, Money $expected): void
    {
        $type = new CHFType();

        $actual = $type->parseValue($input);
        self::assertInstanceOf(Money::class, $actual);
        self::assertTrue($expected->equals($actual));
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseValueAsFloat(string $input, Money $expected): void
    {
        $type = new CHFType();

        $actual = $type->parseValue((float) $input);
        self::assertInstanceOf(Money::class, $actual);
        self::assertTrue($expected->equals($actual));
    }

    /**
     * @dataProvider providerIntValues
     */
    public function testParseValueAsInt(int $input, Money $expected): void
    {
        $type = new CHFType();
        $actual = $type->parseValue($input);
        self::assertInstanceOf(Money::class, $actual);
        self::assertSame((int) $expected->getAmount(), (int) $actual->getAmount());
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseLiteral(string $input, Money $expected): void
    {
        $type = new CHFType();
        $ast = new StringValueNode(['value' => $input]);

        $actual = $type->parseLiteral($ast);
        self::assertInstanceOf(Money::class, $actual);
        self::assertTrue($expected->equals($actual));
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseLiteralAsFloat(string $input, Money $expected): void
    {
        $type = new CHFType();
        $ast = new FloatValueNode(['value' => $input]);

        $actual = $type->parseLiteral($ast);
        self::assertInstanceOf(Money::class, $actual);
        self::assertTrue($expected->equals($actual));
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseLiteralAsInt(string $input, Money $expected): void
    {
        $type = new CHFType();
        $ast = new IntValueNode(['value' => $input]);

        $actual = $type->parseLiteral($ast);
        self::assertInstanceOf(Money::class, $actual);
        self::assertTrue($expected->equals($actual));
    }

    /**
     * @dataProvider providerInvalidValues
     */
    public function testParseValueThrowsWithInvalidValue(string $invalidValue): void
    {
        $type = new CHFType();

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Query error: Not a valid Money: ');
        $type->parseValue($invalidValue);
    }

    public function testParseValueThrowsWithInvalidValueASD(): void
    {
        $type = new CHFType();

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cannot represent value as Money: instance of stdClass');
        $type->parseValue(new stdClass());
    }

    /**
     * @dataProvider providerInvalidValues
     */
    public function testParseLiteralThrowsWithInvalidValue(string $invalidValue): void
    {
        $type = new CHFType();
        $ast = new StringValueNode(['value' => $invalidValue]);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Query error: Not a valid Money: ');
        $type->parseLiteral($ast);
    }

    public static function providerValues(): array
    {
        return [
            ['2', Money::CHF(200)],
            ['2.95', Money::CHF(295)],
            ['0', Money::CHF(0)],
            ['9.00', Money::CHF(900)],
            ['-9.00', Money::CHF(-900)],
            ['-0.00', Money::CHF(0)],
        ];
    }

    public static function providerIntValues(): array
    {
        return [
            [2, Money::CHF(200)],
            [0, Money::CHF(0)],
            [9, Money::CHF(900)],
            [-9, Money::CHF(-900)],
        ];
    }

    public static function providerInvalidValues(): array
    {
        return [
            'non numeric' => ['foo'],
            'too many decimals' => ['1.123'],
            'exponential' => ['1e10'],
            'empty string' => [''],
            'only negative sign' => ['-'],
        ];
    }
}
