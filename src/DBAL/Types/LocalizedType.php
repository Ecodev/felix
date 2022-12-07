<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;

/**
 * Type specialized to store localized data as JSON.
 *
 * PHP values are expected to be an array of localized values indexed by language. The array
 * might be empty, but it can never be null or empty string.
 *
 * For convenience of DB operation the DB value might be null or an empty string, in which case
 * the PHP value will be an empty array. This allows for easy INSERT/UPDATE, and save two bytes
 * in case of empty array.
 */
final class LocalizedType extends JsonType
{
    public function getName(): string
    {
        return 'localized';
    }

    /**
     * @param null|string $value
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $val = parent::convertToPHPValue($value, $platform);

        if (!is_array($val)) {
            throw ConversionException::conversionFailedUnserialization('json', 'value in DB is not a JSON encoded associative array');
        }

        return $val;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        if (!is_array($value)) {
            throw ConversionException::conversionFailedSerialization($value, 'json', 'value must be a PHP array');
        }

        if (!$value) {
            return '';
        }

        // @phpstan-ignore-next-line
        return parent::convertToDatabaseValue($value, $platform);
    }
}
