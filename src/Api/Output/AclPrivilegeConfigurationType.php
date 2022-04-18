<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Output;

use GraphQL\Type\Definition\ObjectType;

class AclPrivilegeConfigurationType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'AclPrivilegeConfiguration',
            'description' => 'Describe an ACL privilege configuration',
            'fields' => [
                'privilege' => [
                    'type' => self::string(),
                    'description' => 'Name of the ACL privilege',
                ],
                'allowed' => [
                    'type' => self::nonNull(self::boolean()),
                    'description' => 'Whether the privilege is allowed',
                ],
                'allowIf' => [
                    'type' => self::nonNull(self::listOf(self::nonNull(self::string()))),
                    'description' => 'List of all human readable conditions that would allow the privilege',
                ],
                'denyIf' => [
                    'type' => self::nonNull(self::listOf(self::nonNull(self::string()))),
                    'description' => 'List of all human readable conditions that would allow the privilege',
                ],
            ],
        ];

        parent::__construct($config);
    }
}
