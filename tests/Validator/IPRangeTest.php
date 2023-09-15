<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Validator;

use Ecodev\Felix\Validator\IPRange;
use PHPUnit\Framework\TestCase;

class IPRangeTest extends TestCase
{
    /**
     * @dataProvider IPv4Data
     * @dataProvider IPv6Data
     */
    public function testMatches(bool $result, string $remoteAddr, string $cidr): void
    {
        self::assertSame($result, IPRange::matches($remoteAddr, $cidr));
    }

    public static function IPv4Data(): iterable
    {
        return [
            'valid - exact (no mask; /32 equiv)' => [true, '192.168.1.1', '192.168.1.1'],
            'valid - entirety of class-c (/1)' => [true, '192.168.1.1', '192.168.1.1/1'],
            'valid - class-c private subnet (/24)' => [true, '192.168.1.1', '192.168.1.0/24'],
            'valid - any subnet (/0)' => [true, '1.2.3.4', '0.0.0.0/0'],
            'valid - subnet expands to all' => [true, '1.2.3.4', '192.168.1.0/0'],
            'invalid - class-a invalid subnet' => [false, '192.168.1.1', '1.2.3.4/1'],
            'invalid - CIDR mask out-of-range' => [false, '192.168.1.1', '192.168.1.1/33'],
            'invalid - invalid cidr notation' => [false, '1.2.3.4', '256.256.256/0'],
            'invalid - invalid IP address' => [false, 'an_invalid_ip', '192.168.1.0/24'],
            'invalid - empty IP address' => [false, '', '1.2.3.4/1'],
            'invalid - proxy wildcard' => [false, '192.168.20.13', '*'],
            'invalid - proxy missing netmask' => [false, '192.168.20.13', '0.0.0.0'],
            'invalid - request IP with invalid proxy wildcard' => [false, '0.0.0.0', '*'],
        ];
    }

    public static function IPv6Data(): iterable
    {
        return [
            'valid - ipv4 subnet' => [true, '2a01:198:603:0:396e:4789:8e99:890f', '2a01:198:603:0::/65'],
            'valid - exact' => [true, '0:0:0:0:0:0:0:1', '::1'],
            'valid - all subnets' => [true, '0:0:603:0:396e:4789:8e99:0001', '::/0'],
            'valid - subnet expands to all' => [true, '0:0:603:0:396e:4789:8e99:0001', '2a01:198:603:0::/0'],
            'invalid - not in subnet' => [false, '2a00:198:603:0:396e:4789:8e99:890f', '2a01:198:603:0::/65'],
            'invalid - does not match exact' => [false, '2a01:198:603:0:396e:4789:8e99:890f', '::1'],
            'invalid - compressed notation, does not match exact' => [false, '0:0:603:0:396e:4789:8e99:0001', '::1'],
            'invalid - garbage IP' => [false, '}__test|O:21:&quot;JDatabaseDriverMysqli&quot;:3:{s:2', '::1'],
            'invalid - invalid cidr' => [false, '2a01:198:603:0:396e:4789:8e99:890f', 'unknown'],
            'invalid - empty IP address' => [false, '', '::1'],
        ];
    }
}
