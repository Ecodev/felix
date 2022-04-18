<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Acl;

use Ecodev\Felix\Acl\Assertion\IsMyself;
use Ecodev\Felix\Acl\Assertion\NamedAssertion;
use Ecodev\Felix\Acl\DebugAcl;
use Ecodev\Felix\Acl\MultipleRoles;
use Exception;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use PHPUnit\Framework\TestCase;

class DebugAclTest extends TestCase
{
    private DebugAcl $acl;

    private NamedAssertion $adminAssertion;

    protected function setUp(): void
    {
        $this->acl = new DebugAcl();

        $this->acl->addRole('member');
        $this->acl->addRole('admin');

        $this->acl->addResource('user');
        $this->acl->addResource('post');

        $this->adminAssertion = new class() implements NamedAssertion {
            public function assert(Acl $acl, ?RoleInterface $role = null, ?ResourceInterface $resource = null, $privilege = null): never
            {
                throw new Exception('Assertion should never be run in debug version of ACL');
            }

            public function getName(): string
            {
                return 'admin assertion';
            }
        };
    }

    public function testPrivileges(): void
    {
        self::assertSame([], $this->acl->getPrivileges());

        $this->acl->allow('member', 'user', 'read');
        self::assertSame(['read'], $this->acl->getPrivileges());

        $this->acl->allow('member', 'post', 'read');
        self::assertSame(['read'], $this->acl->getPrivileges());

        $this->acl->allow('admin', 'post', ['create', 'unusual-privilege']);
        $this->acl->deny('admin', 'post', ['denied-privilege']);
        self::assertSame(['create', 'read', 'denied-privilege', 'unusual-privilege'], $this->acl->getPrivileges());

        $this->acl->allow('admin', 'post', null);
        self::assertSame([null, 'create', 'read', 'denied-privilege', 'unusual-privilege'], $this->acl->getPrivileges());

        self::assertSame(
            [
                'privilege' => 'create',
                'allowed' => false,
                'allowIf' => [],
                'denyIf' => [],
            ],
            $this->acl->show('member', 'user', 'create')
        );
    }

    public function testNamedAssertionsWithAllow(): void
    {
        $this->acl->allow('member', 'user', 'read', new IsMyself());
        $this->acl->allow('admin', 'user', 'read', $this->adminAssertion);

        self::assertSame(
            [
                'privilege' => 'read',
                'allowed' => false,
                'allowIf' => ["c'est moi-même"],
                'denyIf' => [],
            ],
            $this->acl->show('member', 'user', 'read')
        );

        self::assertSame(
            [
                'privilege' => 'read',
                'allowed' => false,
                'allowIf' => ['admin assertion'],
                'denyIf' => [],
            ],
            $this->acl->show('admin', 'user', 'read')
        );

        self::assertSame(
            [
                'privilege' => 'read',
                'allowed' => false,
                'allowIf' => ['admin assertion', "c'est moi-même"],
                'denyIf' => [],
            ],
            $this->acl->show(new MultipleRoles(['member', 'admin']), 'user', 'read')
        );

        self::assertSame(
            [
                'privilege' => 'non-existing-privilege',
                'allowed' => false,
                'allowIf' => [],
                'denyIf' => [],
            ],
            $this->acl->show('member', 'user', 'non-existing-privilege')
        );

        self::assertSame(
            [
                'privilege' => null,
                'allowed' => false,
                'allowIf' => ["c'est moi-même"],
                'denyIf' => [],
            ],
            $this->acl->show('member', 'user', null)
        );
    }

    public function testNamedAssertionsWithDeny(): void
    {
        $this->acl->allow('member', 'user', null);
        $this->acl->allow('admin', 'user', null);
        $this->acl->deny('member', 'user', 'read', new IsMyself());
        $this->acl->deny('admin', 'user', 'read', $this->adminAssertion);

        self::assertSame(
            [
                'privilege' => 'read',
                'allowed' => false,
                'allowIf' => [],
                'denyIf' => ["c'est moi-même"],
            ],
            $this->acl->show('member', 'user', 'read')
        );

        self::assertSame(
            [
                'privilege' => 'read',
                'allowed' => false,
                'allowIf' => [],
                'denyIf' => ['admin assertion'],
            ],
            $this->acl->show('admin', 'user', 'read')
        );

        self::assertSame(
            [
                'privilege' => 'read',
                'allowed' => false,
                'allowIf' => [],
                'denyIf' => ['admin assertion', "c'est moi-même"],
            ],
            $this->acl->show(new MultipleRoles(['member', 'admin']), 'user', 'read')
        );

        self::assertSame(
            [
                'privilege' => 'non-existing-privilege',
                'allowed' => true,  // True because allowed via the `null` wildcard
                'allowIf' => [],
                'denyIf' => [],
            ],
            $this->acl->show('member', 'user', 'non-existing-privilege')
        );
    }
}
