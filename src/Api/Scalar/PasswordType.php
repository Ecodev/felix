<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

final class PasswordType extends AbstractStringBasedType
{
    /**
     * @var string
     */
    public $description = 'A password is a string of at least 12 characters';

    /**
     * Validate a password.
     */
    protected function isValid(?string $value): bool
    {
        return is_string($value) && mb_strlen($value) >= 12;
    }
}
