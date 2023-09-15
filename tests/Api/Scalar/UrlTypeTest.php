<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Scalar;

use Ecodev\Felix\Api\Scalar\UrlType;

final class UrlTypeTest extends AbstractStringBasedType
{
    public function createType(): \Ecodev\Felix\Api\Scalar\AbstractStringBasedType
    {
        return new UrlType();
    }

    public function getTypeName(): string
    {
        return 'Url';
    }

    public static function providerValues(): iterable
    {
        return [

            ['http://www.example.com', 'http://www.example.com', true],
            ['https://www.example.com', 'https://www.example.com', true],
            ['http://example.com', 'http://example.com', true],
            ['http://www.example.com/path', 'http://www.example.com/path', true],
            ['http://www.example.com/path#frag', 'http://www.example.com/path#frag', true],
            ['http://www.example.com/path?param=1', 'http://www.example.com/path?param=1', true],
            ['http://www.example.com/path?param=1#fra', 'http://www.example.com/path?param=1#fra', true],
            ['http://t.co', 'http://t.co', true],
            ['http://www.t.co', 'http://www.t.co', true],
            ['http://a-b.c.t.co', 'http://a-b.c.t.co', true],
            ['http://aa.com', 'http://aa.com', true],
            ['http://www.example', 'http://www.example', true], // this is indeed valid because `example` could be a TLD
            ['https://example.com:4200/subscribe', 'https://example.com:4200/subscribe', true],
            ['https://example-.com', 'https://example-.com', true], // this is not conform to rfc1738, but we tolerate it for simplicity’s sake

            ['www.example.com', 'www.example.com', false],
            ['example.com', 'example.com', false],
            ['www.example', 'www.example', false],
            ['http://example', 'http://example', false],
            ['www.example#.com', 'www.example#.com', false],
            ['www.t.co', 'www.t.co', false],
            ['file:///C:/folder/file.pdf', 'file:///C:/folder/file.pdf', false],

            ['', '', true],
            [null, null, false],
            [' ', ' ', false],
        ];
    }
}
