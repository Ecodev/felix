<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use Cake\Chronos\Chronos;
use DateTimeZone;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

final class ChronosType extends ScalarType
{
    public ?string $description = 'A date with time and timezone.';

    /**
     * Serializes an internal value to include in a response.
     */
    public function serialize(mixed $value): mixed
    {
        if ($value instanceof Chronos) {
            return $value->format('c');
        }

        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     */
    public function parseValue(mixed $value): ?Chronos
    {
        if (!is_string($value)) {
            throw new Error('Cannot represent value as Chronos date: ' . Utils::printSafe($value));
        }

        if ($value === '') {
            return null;
        }

        $date = new Chronos($value);
        $date = $date->setTimezone(new DateTimeZone(date_default_timezone_get()));

        return $date;
    }

    /**
     * Parses an externally provided literal value to use as an input (e.g. in Query AST).
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): ?Chronos
    {
        // Note: throwing GraphQL\Error\Error vs \UnexpectedValueException to benefit from GraphQL
        // error location in query:
        if (!($valueNode instanceof StringValueNode)) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, $valueNode);
        }

        return $this->parseValue($valueNode->value);
    }
}
