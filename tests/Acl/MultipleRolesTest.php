<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Acl;

use Ecodev\Felix\Acl\MultipleRoles;
use PHPUnit\Framework\TestCase;

class MultipleRolesTest extends TestCase
{
    public function testEverything(): void
    {
        $roles = new MultipleRoles();
        self::assertSame([], $roles->getRoles(), 'must be empty');
        self::assertSame('[]', (string) $roles);

        $roles->addRole('writer');
        self::assertSame(['writer'], $roles->getRoles(), 'must be added');
        self::assertTrue($roles->has('writer'), 'must be existing');
        self::assertFalse($roles->has('foo'), 'must not be existing');
        self::assertTrue($roles->has('foo', 'writer'), 'at least one of them');
        self::assertFalse($roles->has('foo', 'bar'), 'none of of them');
        self::assertFalse($roles->has());
        self::assertSame('[writer]', (string) $roles);

        $roles->addRole('writer');
        self::assertSame('[writer]', (string) $roles, 'do not duplicate roles');

        $roles2 = new MultipleRoles(['anonymous', 'admin']);
        self::assertSame(['admin', 'anonymous'], $roles2->getRoles(), 'must be created with roles');
        self::assertSame('[admin, anonymous]', (string) $roles2);

        foreach ($roles2->getRoles() as $role) {
            $roles->addRole($role);
        }
        self::assertSame(['admin', 'anonymous', 'writer'], $roles->getRoles(), 'must be merged');
        self::assertSame('[admin, anonymous, writer]', (string) $roles);

        $roles2->addRole('contact');
        $roles2->addRole('reader');
        self::assertSame(['admin', 'anonymous', 'contact', 'reader'], $roles2->getRoles(), 'array can be added');
        self::assertSame('[admin, anonymous, contact, reader]', (string) $roles2);
    }

    public function testGetRoleId(): void
    {
        $role = new MultipleRoles();

        $this->expectExceptionMessage('This should never be called. If it is, then it means this class is not used correctly');
        $role->getRoleId();
    }
}
