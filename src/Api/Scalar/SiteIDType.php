<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use GraphQL\Type\Definition\IDType;

// Mock a SiteID GraphQL type without the need of a Site model
class SiteIDType extends IDType
{
    public $name = 'SiteID';

    public $description = 'The ID of a site where a door is located';
}
