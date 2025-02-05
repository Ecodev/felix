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

    public function prepare(string $sql): DriverStatement
    {
        return new Statement(parent::prepare($sql), $sql);
    }

    public function query(string $sql): Result
    {
        _log()->debug('Executing query: {sql}', ['sql' => $sql]);

        return parent::query($sql);
    }

    public function exec(string $sql): int|string
    {
        _log()->debug('Executing statement: {sql}', ['sql' => $sql]);

        return parent::exec($sql);
    }

    public function beginTransaction(): void
    {
        _log()->debug('Beginning transaction');

        parent::beginTransaction();
    }

    public function commit(): void
    {
        _log()->debug('Committing transaction');

        parent::commit();
    }

    public function rollBack(): void
    {
        _log()->debug('Rolling back transaction');

        parent::rollBack();
    }
}
