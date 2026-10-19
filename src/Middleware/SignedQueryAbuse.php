<?php

declare(strict_types=1);

namespace Ecodev\Felix\Middleware;

use Ecodev\Felix\Log\Handler\NoMailLogging;
use Exception;

/**
 * Exception that represent an bad usage, or plain abuse, of our signed query system.
 */
class SignedQueryAbuse extends Exception implements NoMailLogging
{
    public function __construct(string $message)
    {
        parent::__construct($message, 400);
    }
}
