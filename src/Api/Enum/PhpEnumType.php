<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Enum;

use ReflectionClass;
use ReflectionClassConstant;

/**
 * Like `\GraphQL\Type\Definition\PhpEnumType` with added support for `LocalizedPhpEnumType`.
 */
final class PhpEnumType extends \GraphQL\Type\Definition\PhpEnumType
{
    protected function extractDescription(ReflectionClassConstant|ReflectionClass $reflection): ?string
    {
        if ($reflection instanceof ReflectionClassConstant) {
            $value = $reflection->getValue();
            if ($value instanceof LocalizedPhpEnumType) {
                return $reflection->getValue()->getDescription();
            }
        }

        return parent::extractDescription($reflection);
    }
}
