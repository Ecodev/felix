<?php

declare(strict_types=1);

namespace EcodevTests\Felix\DBAL\Types;

use Ecodev\Felix\DBAL\Types\SetType;

class ExampleSet extends SetType
{
    protected function getPossibleValues(): array
    {
        return ['value1', 'value2'];
    }
}
