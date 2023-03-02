<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use Ecodev\Felix\Validator\DeliverableEmail;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;

/**
 * Represent an email address.
 *
 * This exceptionally accept empty string as null because email address are often unique
 * in DB and thus can never be empty string to indicate absence of email. So we simplify
 * the client work by accepting empty string and transparently transforming into a null value.
 */
final class EmailType extends AbstractStringBasedType
{
    /**
     * Validate a email.
     */
    protected function isValid(?string $value): bool
    {
        $validator = new DeliverableEmail();

        return $value === null || $validator->isValid($value);
    }

    public function serialize(mixed $value): mixed
    {
        if ($value === '') {
            return null;
        }

        return parent::serialize($value);
    }

    public function parseValue(mixed $value): ?string
    {
        if ($value === '') {
            return null;
        }

        return parent::parseValue($value);
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): ?string
    {
        if ($valueNode instanceof StringValueNode && $valueNode->value === '') {
            return null;
        }

        return parent::parseLiteral($valueNode, $variables);
    }
}
