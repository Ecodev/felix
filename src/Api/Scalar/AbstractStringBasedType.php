<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use UnexpectedValueException;

abstract class AbstractStringBasedType extends ScalarType
{
    /**
     * Validate value.
     */
    abstract protected function isValid(?string $value): bool;

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize(mixed $value): mixed
    {
        // Assuming internal representation is always correct:
        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     */
    public function parseValue(mixed $value): ?string
    {
        $typeOk = is_string($value) || $value === null;
        if (!$typeOk || !$this->isValid($value)) {
            throw new UnexpectedValueException('Query error: Not a valid ' . $this->name . ': ' . Utils::printSafe($value));
        }

        return $value;
    }

    /**
     * Parses an externally provided literal value to use as an input (e.g. in Query AST).
     */
    public function parseLiteral(Node $ast, ?array $variables = null): ?string
    {
        // Note: throwing GraphQL\Error\Error vs \UnexpectedValueException to benefit from GraphQL
        // error location in query:
        if (!($ast instanceof StringValueNode)) {
            throw new Error('Query error: Can only parse strings got: ' . $ast->kind, $ast);
        }

        if (!$this->isValid($ast->value)) {
            throw new Error('Query error: Not a valid ' . $this->name, $ast);
        }

        return $ast->value;
    }
}
