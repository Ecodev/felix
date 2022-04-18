<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Output;

use GraphQL\Type\Definition\ObjectType;

class AclResourceConfigurationType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'AclResourceConfiguration',
            'description' => 'Describe an ACL resource configuration',
            'fields' => [
                'resource' => [
                    'type' => self::nonNull(self::string()),
                    'description' => 'Name of the ACL resource',
                ],
                'privileges' => [
                    'type' => self::nonNull(self::listOf(self::nonNull(_types()->get(AclPrivilegeConfigurationType::class)))),
                    'description' => 'List of all privileges for that resource',
                ],
            ],
        ];

        parent::__construct($config);
    }
}
