<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use JsonException;

/**
 * Type specialized to store localized data as JSON.
 *
 * PHP values are expected to be an array of localized values indexed by language. The array
 * might be empty, but it can never be null or empty string.
 *
 * DB values are constrained by the database, so they must always be valid JSON, such as the minimal data `{}`.
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
            return '{}';
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            throw ConversionException::conversionFailedSerialization($value, 'json', $e->getMessage(), $e);
        }
    }
}
