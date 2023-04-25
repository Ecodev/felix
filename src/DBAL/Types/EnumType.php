<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Exception;
use InvalidArgumentException;
use ReflectionClass;

abstract class EnumType extends Type
{
    public function getSqlDeclaration(array $column, AbstractPlatform $platform): string
    {
        $possibleValues = $this->getPossibleValues();
        $quotedPossibleValues = implode(', ', array_map(fn (string $str) => "'" . $str . "'", $possibleValues));

        $sql = 'ENUM(' . $quotedPossibleValues . ')';

        return $sql;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null || '' === $value) {
            return null;
        }

        if (!in_array($value, $this->getPossibleValues(), true)) {
            throw new InvalidArgumentException("Invalid '" . $value . "' value fetched from database for enum " . $this->getName());
        }

        return (string) $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null || '' === $value) {
            return null;
        }

        if (!in_array($value, $this->getPossibleValues(), true)) {
            throw new InvalidArgumentException("Invalid '" . $value . "' value to be stored in database for enum " . $this->getName());
        }

        return (string) $value;
    }

    /**
     * Return all possibles values as an array of string.
     *
     * @return string[]
     */
    abstract protected function getPossibleValues(): array;

    /**
     * Returns the type name based on actual class name.
     */
    public function getName(): string
    {
        $class = new ReflectionClass($this);
        $shortClassName = $class->getShortName();
        $typeName = preg_replace('/Type$/', '', $shortClassName);

        if ($typeName === null) {
            throw new Exception('Could not extract enum name from class name');
        }

        return $typeName;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return ['enum'];
    }
}
