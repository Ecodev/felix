<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

final class TokenType extends AbstractStringBasedType
{
    /**
     * @var string
     */
    public $description = 'A user token is a lowercase hexadecimal string of 32 characters or 6 digits.';

    /**
     * Validate a token.
     */
    protected function isValid(?string $value): bool
    {
        return is_string($value) && preg_match('/^([\da-z]{32}|\d{6})$/', $value);
    }
}
