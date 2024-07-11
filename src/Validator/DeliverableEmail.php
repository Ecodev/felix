<?php

declare(strict_types=1);

namespace Ecodev\Felix\Validator;

use Laminas\Validator\EmailAddress;

/**
 * Validate an email address according to RFC, and also that it is publicly deliverable (not "root@localhost" or "root@127.0.0.1").
 *
 * This is meant to replace **all** usages of Laminas too permissive `\Laminas\Validator\EmailAddress`
 */
class DeliverableEmail
{
    public function isValid(string $value): bool
    {
        // This regexp should be kep in sync with the original one in Natural
        if (!preg_match('/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[^@ ]+\.[^@]+$/u', $value)) {
            return false;
        }

        $validator = new EmailAddress();

        return $validator->isValid($value);
    }
}
