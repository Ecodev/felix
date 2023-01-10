<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Traits;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;

class MariaDbQuotingConnection extends Connection
{
    /**
     * This replicate MariaDB quoting but without a real connection to DB for ease of testing.
     *
     * @param null|int|string|Type $type
     */
    public function quote(mixed $value, $type = ParameterType::STRING)
    {
        $quoted = "'" . str_replace(["'", "\n"], ["\\'", '\\n'], (string) $value) . "'";

        return $quoted;
    }
}
