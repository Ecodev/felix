<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use Ecodev\Felix\Api\Enum\LocalizedPhpEnumType;

enum TestEnum: string implements LocalizedPhpEnumType
{
    case key1 = 'value1';
    case key2 = 'value2';

    public function getDescription(): string
    {
        return match ($this) {
            TestEnum::key1 => 'custom description for key 1',
            TestEnum::key2 => 'other for key 2',
        };
    }
}
