<?php

declare(strict_types=1);

namespace Ecodev\Felix;

use Ecodev\Felix\Model\Model;
use GraphQL\Doctrine\Definition\EntityID;
use ReflectionClass;

final class Utility
{
    /**
     * Returns the short class name of any object, eg: Application\Model\Calendar => Calendar.
     *
     * @param class-string|object $object
     */
    public static function getShortClassName(object|string $object): string
    {
        $reflect = new ReflectionClass($object);

        return $reflect->getShortName();
    }

    /**
     * Replace EntityID model and don't touch other values.
     *
     * @param ?array $data mix of objects and scalar values
     *
     * @return ($data is null ? null : array)
     */
    public static function entityIdToModel(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($data as &$value) {
            if ($value instanceof EntityID) {
                $value = $value->getEntity();
            }
        }

        return $data;
    }

    /**
     * Replace object by their ID in the array and don't touch other values.
     *
     * Support both AbstractModel and EntityID.
     *
     * @param ?array $data mix of objects and scalar values
     *
     * @return ($data is null ? null : array)
     */
    public static function modelToId(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($data as &$value) {
            if ($value instanceof Model || $value instanceof EntityID) {
                $value = $value->getId();
            }
        }

        return $data;
    }

    /**
     * Removes duplicate values from an array by using strict comparison.
     *
     * So it can be used with objects, whereas the native `array_unique` cannot.
     */
    public static function unique(array $array): array
    {
        $result = [];
        foreach ($array as $value) {
            if (!in_array($value, $result, true)) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Safely quotes an array of values for an SQL statement.
     *
     * The values are quoted and then returned as a comma-separated string, so:
     *
     * ```php
     * Utility::quoteArray(['foo bar', 2]); // "'foo bar', '2'"
     * ```
     *
     * @param array<null|scalar> $values
     */
    public static function quoteArray(array $values): string
    {
        $connection = _em()->getConnection();
        $quoted = [];
        foreach ($values as $value) {
            $quoted[] = $connection->quote((string) $value);
        }

        return implode(', ', $quoted);
    }

    /**
     * Return the domain to be used for cookie.
     *
     * We look for domain name to build the string ".mydomain.com" to specify
     * that cookies (session) are available on all subdomains.
     *
     * This will not work for domain without TLD such as "localhost", because
     * RFC specify the domain string must contain two "." characters.
     */
    public static function getCookieDomain(string $input): ?string
    {
        if ($input && preg_match('/([^.]+\.[^.:]+)(:\d+)?$/', $input, $match)) {
            $cookieDomain = '.' . $match[1];
        } else {
            $cookieDomain = null;
        }

        return $cookieDomain;
    }

    /**
     * Concatenate all given iterables into a new iterator that yields key/value pairs exactly as if we iterated through each iterable sequentially.
     */
    public static function concat(iterable ...$iterables): iterable
    {
        foreach ($iterables as $iterable) {
            foreach ($iterable as $k => $v) {
                yield $k => $v;
            }
        }
    }

    /**
     * Return a new iterable with only key/value pairs that match the given keys.
     */
    public static function filterByKeys(iterable $things, string ...$keyToKeep): iterable
    {
        foreach ($things as $k => $v) {
            if (in_array($k, $keyToKeep, true)) {
                yield $k => $v;
            }
        }
    }
}
