<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use GraphQL\Type\Definition\IntType;

class OtpType extends IntType
{
    /**
     * @var string
     */
    public $description = 'One time code composed of up to 6 digits';

    public const MIN_INT = 0;
    public const MAX_INT = 999999;
}
