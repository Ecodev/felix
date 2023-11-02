<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

class OtpType extends AbstractStringBasedType
{
    public ?string $description = 'One time passcode composed of only digits';

    /**
     * Validate an OTP.
     */
    protected function isValid(?string $value): bool
    {
        return is_string($value) && preg_match('/^\d{6}+$/', $value);
    }
}
