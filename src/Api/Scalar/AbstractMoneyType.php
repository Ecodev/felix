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
use Money\Money;
use UnexpectedValueException;

abstract class AbstractMoneyType extends ScalarType
{
    abstract protected function createMoney(string $value): Money;

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize(mixed $value): mixed
    {
        if (is_numeric($value)) {
            $value = $this->createMoney((string) $value);
        }

        if ($value instanceof Money) {
            return bcdiv($value->getAmount(), '100', 2);
        }

        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     */
    public function parseValue(mixed $value): Money
    {
        if (!is_scalar($value)) {
            throw new UnexpectedValueException('Cannot represent value as Money: ' . Utils::printSafe($value));
        }

        $value = (string) $value;

        if (!$this->isValid($value)) {
            throw new Error('Query error: Not a valid Money: ' . Utils::printSafe($value));
        }

        $money = $this->createMoney(bcmul($value, '100', 2));

        return $money;
    }

    /**
     * Parses an externally provided literal value to use as an input (e.g. in Query AST).
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): Money
    {
        if ($valueNode instanceof StringValueNode || $valueNode instanceof IntValueNode || $valueNode instanceof FloatValueNode) {
            return $this->parseValue($valueNode->value);
        }

        throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, $valueNode);
    }

    private function isValid(mixed $value): bool
    {
        return is_string($value) && preg_match('~^-?\d+(\.\d{0,2})?$~', $value);
    }
}
