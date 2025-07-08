<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use Ecodev\Felix\Service\TransportFactory;
use PHPUnit\Framework\TestCase;

class TransportFactoryTest extends TestCase
{
    /**
     * @dataProvider providerDsn
     */
    public function testDsn(null|array|string $input, string $expected): void
    {
        $actual = TransportFactory::dsn($input);

        self::assertIsArray(parse_url($expected), 'resulting value must be parseable');
        self::assertSame($expected, $actual);
    }

    public static function providerDsn(): iterable
    {
        yield [null, 'null://null'];
        yield ['', 'null://null'];
        yield ['smtp://my-user:my-pass@my-host:123', 'smtp://my-user:my-pass@my-host:123'];
        yield [[], 'null://null'];
        yield [['host' => '', 'port' => '', 'user' => '', 'password' => ''], 'null://null'];
        yield [['host' => '', 'port' => '', 'user' => 'my-user', 'password' => 'my-pass'], 'null://null'];
        yield 'port has default value' => [['host' => 'my-host'], 'smtp://my-host:587'];
        yield 'port has default value bis' => [['host' => 'my-host', 'port' => ''], 'smtp://my-host:587'];
        yield 'custom port' => [['host' => 'my-host', 'port' => '123'], 'smtp://my-host:123'];
        yield 'new style credentials' => [['host' => 'my-host', 'port' => '123', 'user' => 'my-user', 'password' => 'my-pass'], 'smtp://my-user:my-pass@my-host:123'];
        yield 'okpilot style credentials' => [['host' => 'my-host', 'port' => '123', 'connection_config' => ['username' => 'my-user', 'password' => 'my-pass']], 'smtp://my-user:my-pass@my-host:123'];
        yield 'escape' => [
            [
                'host' => 'smtp.example.com',
                'port' => 465,
                'connection_config' => [
                    'username' => 'john@example.com',
                    'password' => 'foo#:@/',
                    'ssl' => 'ssl',
                ],
            ],
            'smtp://john%40example.com:foo%23%3A%40%2F@smtp.example.com:465'];
    }
}
