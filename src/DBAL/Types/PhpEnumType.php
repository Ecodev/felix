<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use BackedEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use GraphQL\Utils\Utils;
use InvalidArgumentException;

/**
 * Enum based on native PHP backed enum.
 */
abstract class PhpEnumType extends EnumType
{
    /**
     * Returns the FQCN of the native PHP enum.
     *
     * @return class-string<BackedEnum>
     */
    abstract protected function getEnumType(): string;

    protected function getPossibleValues(): array
    {
        return array_map(fn (BackedEnum $str) => $str->value, $this->getEnumType()::cases());
    }

    /**
     * @param ?string $value
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BackedEnum
    {
        if ($value === null || '' === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException("Invalid '" . Utils::printSafe($value) . "' value fetched from database for enum " . $this->getName());
        }

        return $this->getEnumType()::from($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_object($value) || !is_a($value, $this->getEnumType())) {
            throw new InvalidArgumentException("Invalid '" . Utils::printSafe($value) . "' value to be stored in database for enum " . $this->getName());
        }

        return $value->value;
    }
}
