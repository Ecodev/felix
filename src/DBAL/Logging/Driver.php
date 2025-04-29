<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Logging;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Ecodev\Felix\Log\Handler\DbHandler;
use SensitiveParameter;

final class Driver extends AbstractDriverMiddleware
{
    public function __construct(
        DriverInterface $driver,
        private readonly DbHandler $dbHandler,
        private readonly bool $logSql,
    ) {
        parent::__construct($driver);
    }

    public function connect(#[SensitiveParameter] array $params): DriverInterface\Connection
    {
        _log()->debug('Connecting to DB', $this->maskPassword($params));

        // Don't bother to wrap the connection if we will never log SQL queries to file...
        $connection = parent::connect($params);
        if ($this->logSql) {
            $connection = new Connection($connection);
        }

        // ... but always notify that we are now connected. so we can log other things to DB
        $this->dbHandler->enable();

        return $connection;
    }

    /**
     * @param array<string,mixed> $params
     *
     * @return array<string,mixed>
     */
    private function maskPassword(#[SensitiveParameter] array $params): array
    {
        if (isset($params['password'])) {
            $params['password'] = '***REDACTED***';
        }

        return $params;
    }
}
