<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use GraphQL\Type\Definition\Description;

enum OtherTestEnum: string
{
    #[Description('static description via webonyx/graphql')]
    case key1 = 'value1';
}
