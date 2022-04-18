<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Field;

use Ecodev\Felix\Acl\Acl;
use Ecodev\Felix\Acl\MultipleRoles;
use Ecodev\Felix\Api\Output\AclResourceConfigurationType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\Type;

abstract class AclConfiguration
{
    public static function build(Acl $acl, EnumType $roleType): array
    {
        return
            [
                'name' => 'aclConfiguration',
                'type' => Type::nonNull(Type::listOf(Type::nonNull(_types()->get(AclResourceConfigurationType::class)))),
                'description' => 'User friendly configuration of the ACL',
                'args' => [
                    'roles' => Type::nonNull(Type::listOf(Type::nonNull($roleType))),
                ],
                'resolve' => function ($root, array $args) use ($acl): array {
                    $roles = $args['roles'];
                    $result = $acl->show(new MultipleRoles($roles));

                    return $result;
                },
            ];
    }
}
