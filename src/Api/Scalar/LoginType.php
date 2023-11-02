<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

class LoginType extends AbstractStringBasedType
{
    public ?string $description = 'A user login is a non-empty string containing only letters, digits, `.`, `_` and `-`.';

    /**
     * Validate a login.
     */
    protected function isValid(?string $value): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9._-]+$/', $value);
    }
}
