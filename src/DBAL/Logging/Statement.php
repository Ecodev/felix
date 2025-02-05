<?php

declare(strict_types=1);

namespace Ecodev\Felix\DBAL\Logging;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;

final class Statement extends AbstractStatementMiddleware
{
    /**
     * @var array<int,mixed>|array<string,mixed>
     */
    private array $params = [];

    public function __construct(
        StatementInterface $statement,
        private readonly string $sql,
    ) {
        parent::__construct($statement);
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $this->params[$param] = $value;

        parent::bindValue($param, $value, $type);
    }

    public function execute(): Result
    {
        $start = microtime(true);
        $result = parent::execute();
        $end = microtime(true);

        _log()->debug($this->sql, [
            'params' => $this->params,
            'time' => number_format(($end - $start) / 1000, 6),
        ]);

        return $result;
    }
}
