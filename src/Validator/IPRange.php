<?php

declare(strict_types=1);

namespace Ecodev\Felix\Validator;

abstract class IPRange
{
    /**
     * Whether the given IP address matches at least one of the given CIDR.
     *
     * @param string[] $cidrs
     */
    final public static function matches(string $ip, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {
            if (self::matchesOne($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private static function matchesOne(string $ip, string $cidr): bool
    {
        if (str_contains($ip, ':')) {
            return self::matchesIPv6($ip, $cidr);
        }

        return self::matchesIPv4($ip, $cidr);
    }

    private static function matchesIPv4(string $ip, string $cidr): bool
    {
        $mask = 32;
        $subnet = $cidr;

        if (str_contains($cidr, '/')) {
            [$subnet, $mask] = explode('/', $cidr, 2);
            $mask = (int) $mask;
        }

        if ($mask < 0 || $mask > 32) {
            return false;
        }

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        if (false === $ip || false === $subnet) {
            // Invalid data
            return false;
        }

        return 0 === substr_compare(
            sprintf('%032b', $ip),
            sprintf('%032b', $subnet),
            0,
            $mask
        );
    }

    private static function matchesIPv6(string $ip, string $cidr): bool
    {
        $mask = 128;
        $subnet = $cidr;

        if (str_contains($cidr, '/')) {
            [$subnet, $mask] = explode('/', $cidr, 2);
            $mask = (int) $mask;
        }

        if ($mask < 0 || $mask > 128) {
            return false;
        }

        $ip = inet_pton($ip);
        $subnet = inet_pton($subnet);

        if (false === $ip || false === $subnet) {
            // Invalid data
            return false;
        }

        // mask 0: if it's a valid IP, it's valid
        if ($mask === 0) {
            return (bool) unpack('n*', $ip);
        }

        /** @see http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet, MW answer */
        $binMask = str_repeat('f', (int) ($mask / 4));
        switch ($mask % 4) {
            case 0:
                break;
            case 1:
                $binMask .= '8';

                break;
            case 2:
                $binMask .= 'c';

                break;
            case 3:
                $binMask .= 'e';

                break;
        }

        $binMask = str_pad($binMask, 32, '0');
        $binMask = pack('H*', $binMask);

        return ($ip & $binMask) === $subnet;
    }
}
