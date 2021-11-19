<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

final class UrlType extends AbstractStringBasedType
{
    /**
     * @var string
     */
    public $description = 'An absolute web URL that must start with `http` or `https`.';

    /**
     * Validate an URL.
     *
     * @param mixed $value
     */
    protected function isValid($value): bool
    {
        // Here we use a naive pattern that should ideally be kept in sync with Natural validator
        return is_string($value) && preg_match('~^https?://(?:[^.\s]+\.)+[^.\s]+$~', $value);
    }
}
