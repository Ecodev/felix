<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Traits;

use Doctrine\DBAL\Connection;

class MariaDbQuotingConnection extends Connection
{
    /**
     * This replicate MariaDB quoting but without a real connection to DB for ease of testing.
     */
    public function quote(string $value): string
    {
        $quoted = "'" . str_replace(["'", "\n"], ["\\'", '\\n'], $value) . "'";

        return $quoted;
    }
}
