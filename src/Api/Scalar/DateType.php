<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use Cake\Chronos\ChronosDate;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use UnexpectedValueException;

final class DateType extends ScalarType
{
    /**
     * @var string
     */
    public $description = 'A date without time, nor timezone.';

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize(mixed $value): mixed
    {
        if ($value instanceof ChronosDate) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     */
    public function parseValue(mixed $value): ChronosDate
    {
        if (!is_string($value)) {
            throw new UnexpectedValueException('Cannot represent value as Chronos date: ' . Utils::printSafe($value));
        }

        $date = ChronosDate::createFromFormat('Y-m-d+', $value);

        return $date;
    }

    /**
     * Parses an externally provided literal value to use as an input (e.g. in Query AST).
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): ChronosDate
    {
        // Note: throwing GraphQL\Error\Error vs \UnexpectedValueException to benefit from GraphQL
        // error location in query:
        if (!($valueNode instanceof StringValueNode)) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, $valueNode);
        }

        return $this->parseValue($valueNode->value);
    }
}
