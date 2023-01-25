<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

final class UrlType extends AbstractStringBasedType
{
    /**
     * @var string
     */
    public $description = 'An absolute web URL that must start with `http` or `https` or be an empty string.';

    /**
     * Validate an URL.
     */
    protected function isValid(?string $value): bool
    {
        // Here we use a naive pattern that should ideally be kept in sync with Natural validator
        return $value === '' || is_string($value) && preg_match('~^https?://(?:[^.\s]+\.)+[^.\s]+$~', $value);
    }
}
