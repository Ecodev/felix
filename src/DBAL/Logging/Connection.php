<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Logging;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as DriverStatement;

final class Connection extends AbstractConnectionMiddleware
{
    public function __construct(ConnectionInterface $connection)
    {
        parent::__construct($connection);
    }

    public function __destruct()
    {
        _log()->debug('Disconnecting');
    }

    public function prepare(string $sql): DriverStatement
    {
        return new Statement(parent::prepare($sql), $sql);
    }

    public function query(string $sql): Result
    {
        return $this->withLog($sql, fn () => parent::query($sql));
    }

    public function exec(string $sql): int|string
    {
        return $this->withLog($sql, fn () => parent::exec($sql));
    }

    public function beginTransaction(): void
    {
        $this->withLog('Beginning transaction', fn () => parent::beginTransaction());
    }

    public function commit(): void
    {
        $this->withLog('Committing transaction', fn () => parent::commit());
    }

    public function rollBack(): void
    {
        $this->withLog('Rolling back transaction', fn () => parent::rollBack());
    }

    /**
     * @template T
     *
     * @param callable(): T $callable
     *
     * @return T whatever the callable returned
     */
    public function withLog(string $message, callable $callable): mixed
    {
        $start = microtime(true);
        $result = $callable();
        $end = microtime(true);

        _log()->debug($message, [
            'time' => number_format($end - $start, 6),
        ]);

        return $result;
    }
}
