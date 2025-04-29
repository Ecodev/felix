<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Logging;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Ecodev\Felix\Log\Handler\DbHandler;

/**
 * Log SQL queries including their timing (so the query will be logged after its execution).
 */
final class Middleware implements MiddlewareInterface
{
    public function __construct(
        private readonly DbHandler $dbHandler,
        private readonly bool $logSql,
    ) {
    }

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new Driver($driver, $this->dbHandler, $this->logSql);
    }
}
