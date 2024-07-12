<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Logging;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use SensitiveParameter;

final class Driver extends AbstractDriverMiddleware
{
    public function __construct(DriverInterface $driver)
    {
        parent::__construct($driver);
    }

    public function connect(#[SensitiveParameter] array $params): Connection
    {
        _log()->debug('Connecting to DB', $this->maskPassword($params));

        return new Connection(parent::connect($params));
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
