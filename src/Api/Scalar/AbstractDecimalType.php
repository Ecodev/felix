<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use GraphQL\Error\Error;
use GraphQL\Language\AST\FloatValueNode;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use UnexpectedValueException;

abstract class AbstractDecimalType extends ScalarType
{
    /**
     * Return the number of digits after the decimal.
     */
    abstract protected function getScale(): int;

    /**
     * Return the minimum accepted value, if any.
     */
    protected function getMinimum(): ?string
    {
        return null;
    }

    /**
     * Return the maximum accepted value, if any.
     */
    protected function getMaximum(): ?string
    {
        return null;
    }

    /**
     * Validate value.
     */
    private function isValid(string $value): bool
    {
        $decimal = $this->getScale();

        if (!preg_match('~^-?\d+(\.\d{0,' . $decimal . '})?$~', $value)) {
            return false;
        }

        $minimum = $this->getMinimum();
        if ($minimum !== null && bccomp($value, $minimum, $decimal) === -1) {
            return false;
        }

        $maximum = $this->getMaximum();
        if ($maximum !== null && bccomp($value, $maximum, $decimal) === 1) {
            return false;
        }

        return true;
    }

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
     *
     * @param null|float|int|string $value
     */
    public function parseValue(mixed $value): string
    {
        $parsedValue = (string) $value;
        if (!$this->isValid($parsedValue)) {
            throw new UnexpectedValueException('Query error: Not a valid ' . $this->name . ': ' . Utils::printSafe($value));
        }

        return $parsedValue;
    }

    /**
     * Parses an externally provided literal value to use as an input (e.g. in Query AST).
     */
    public function parseLiteral(Node $ast, ?array $variables = null): string
    {
        // Note: throwing GraphQL\Error\Error vs \UnexpectedValueException to benefit from GraphQL
        // error location in query:

        if (!($ast instanceof StringValueNode || $ast instanceof IntValueNode || $ast instanceof FloatValueNode)) {
            throw new Error('Query error: Can only parse strings got: ' . $ast->kind, $ast);
        }

        $parsedValue = (string) $ast->value;
        if (!$this->isValid($parsedValue)) {
            throw new Error('Query error: Not a valid ' . $this->name, $ast);
        }

        return $parsedValue;
    }
}
