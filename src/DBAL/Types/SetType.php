<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Exception;
use InvalidArgumentException;
use ReflectionClass;

abstract class SetType extends Type
{
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $possibleValues = $this->getPossibleValues();
        $quotedPossibleValues = implode(', ', array_map(fn ($str) => "'" . $str . "'", $possibleValues));

        $sql = 'SET(' . $quotedPossibleValues . ')';

        return $sql;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?array
    {
        if ($value === null) {
            return null;
        }

        $values = is_string($value) ? preg_split('/,/', $value, -1, PREG_SPLIT_NO_EMPTY) : null;
        if (!$this->isValid($values)) {
            throw new InvalidArgumentException("Invalid '" . $value . "' value fetched from database for set " . $this->getName());
        }

        /** @var array $values */
        return $values;
    }

    public function convertToDatabaseValue($values, AbstractPlatform $platform): ?string
    {
        if ($values === null) {
            return null;
        }

        $value = is_array($values) ? implode(',', $values) : null;
        if (!$this->isValid($values)) {
            throw new InvalidArgumentException("Invalid '" . $value . "' value to be stored in database for set " . $this->getName());
        }

        return $value;
    }

    private function isValid(mixed $values): bool
    {
        if (!is_array($values)) {
            return false;
        }

        $possibleValues = $this->getPossibleValues();
        foreach ($values as $value) {
            if (!in_array($value, $possibleValues, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return all possibles values as an array of string.
     *
     * @return string[]
     */
    abstract protected function getPossibleValues(): array;

    /**
     * Returns the type name based on actual class name and possible values.
     */
    public function getName(): string
    {
        $class = new ReflectionClass($this);
        $shortClassName = $class->getShortName();
        $typeName = preg_replace('/Type$/', '', $shortClassName);

        if ($typeName === null) {
            throw new Exception('Could not extract set name from class name');
        }

        return $typeName;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform)
    {
        return ['set'];
    }
}
