<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Enum;

use BackedEnum;

/**
 * An enum that has a localized description for each case to be shown to end-user.
 */
interface LocalizedPhpEnumType extends BackedEnum
{
    /**
     * Returns the user-friendly, localized description for the case.
     */
    public function getDescription(): string;
}
