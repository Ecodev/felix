<?php

declare(strict_types=1);

namespace Ecodev\Felix\Log\Filter;

use GraphQL\Error\Error;
use Laminas\Log\Filter\FilterInterface;

final class NoMail implements FilterInterface
{
    /**
     * Ignore exception that are explicitly marked as ignored.
     */
    public function filter(array $event): bool
    {
        $exception = $event['extra']['exception'] ?? null;

        if ($exception instanceof NoMailLogging || ($exception instanceof Error && $exception->getPrevious() instanceof NoMailLogging)) {
            return false;
        }

        return true;
    }
}
