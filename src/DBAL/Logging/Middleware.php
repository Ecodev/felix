<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Logging;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;

/**
 * Log SQL queries including their timing (so the query will be logged after its execution).
 */
final class Middleware implements MiddlewareInterface
{
    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new Driver($driver);
    }
}
