<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use GraphQL\Error\Error;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\StringValueNode;
use PHPUnit\Framework\TestCase;

abstract class AbstractStringBasedType extends TestCase
{
    abstract public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType;

    abstract public function getTypeName(): string;

    /**
     * @dataProvider providerValues
     */
    public function testSerialize(?string $input, ?string $expected): void
    {
        $type = $this->createType();
        $actual = $type->serialize($input);
        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseValue(?string $input, ?string $expected, bool $isValid): void
    {
        $type = $this->createType();

        if (!$isValid) {
            $this->expectException(Error::class);
            $this->expectExceptionMessage('Query error: Not a valid ' . $this->getTypeName());
        }

        $actual = $type->parseValue($input);

        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider providerValues
     */
    public function testParseLiteral(string $input, ?string $expected, bool $isValid): void
    {
        $type = $this->createType();
        $ast = new StringValueNode(['value' => $input]);

        if (!$isValid) {
            $this->expectException(Error::class);
            $this->expectExceptionMessage('Query error: Not a valid ' . $this->getTypeName());
        }

        $actual = $type->parseLiteral($ast);

        self::assertSame($expected, $actual);
    }

    abstract public static function providerValues(): iterable;

    public function testParseInvalidNodeWillThrow(): void
    {
        $type = $this->createType();
        $ast = new IntValueNode(['value' => '123']);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Query error: Can only parse strings got: IntValue');
        $type->parseLiteral($ast);
    }
}
