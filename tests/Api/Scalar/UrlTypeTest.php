<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\UrlType;
use GraphQL\Language\AST\StringValueNode;
use PHPUnit\Framework\TestCase;

final class UrlTypeTest extends TestCase
{
    /**
     * @dataProvider providerUrls
     */
    public function testSerialize(?string $input, bool $isValid): void
    {
        $type = new UrlType();
        $actual = $type->serialize($input);
        self::assertSame($input, $actual);
    }

    /**
     * @dataProvider providerUrls
     */
    public function testParseValue(?string $input, bool $isValid): void
    {
        $type = new UrlType();

        if (!$isValid) {
            $this->expectExceptionMessage('Query error: Not a valid Url');
        }

        $actual = $type->parseValue($input);

        self::assertSame($input, $actual);
    }

    /**
     * @dataProvider providerUrls
     */
    public function testParseLiteral(?string $input, bool $isValid): void
    {
        $type = new UrlType();
        $ast = new StringValueNode(['value' => $input]);

        if (!$isValid) {
            $this->expectExceptionMessage('Query error: Not a valid Url');
        }

        $actual = $type->parseLiteral($ast);

        self::assertSame($input, $actual);
    }

    public function providerUrls(): array
    {
        return [
            ['http://www.example.com', true],
            ['https://www.example.com', true],
            ['http://example.com', true],
            ['http://www.example.com/path', true],
            ['http://www.example.com/path#frag', true],
            ['http://www.example.com/path?param=1', true],
            ['http://www.example.com/path?param=1#fra', true],
            ['http://t.co', true],
            ['http://www.t.co', true],
            ['http://a-b.c.t.co', true],
            ['http://aa.com', true],
            ['http://www.example', true], // this is indeed valid because `example` could be a TLD
            ['https://example.com:4200/subscribe', true],
            ['https://example-.com', true], // this is not conform to rfc1738, but we tolerate it for simplicity sake

            ['www.example.com', false],
            ['example.com', false],
            ['www.example', false],
            ['http://example', false],
            ['www.example#.com', false],
            ['www.t.co', false],
            ['file:///C:/folder/file.pdf', false],

            ['', false],
            [null, false],
            [' ', false],
        ];
    }
}
