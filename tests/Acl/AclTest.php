<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Acl;

use Ecodev\Felix\Acl\Acl;
use Ecodev\Felix\Acl\Assertion\IsMyself;
use Ecodev\Felix\Acl\MultipleRoles;
use Ecodev\Felix\Model\CurrentUser;
use EcodevTests\Felix\Blog\Model\User;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use PHPUnit\Framework\TestCase;

final class AclTest extends TestCase
{
    protected function tearDown(): void
    {
        CurrentUser::set(null);
    }

    public function testIsCurrentUserAllowed(): void
    {
        $acl = new class() extends Acl {
            public function __construct()
            {
                $user = $this->createModelResource(User::class);
                $this->addRole('anonymous');
                $this->addRole('member');
                $this->allow('member', [$user], ['update'], new IsMyself());
            }
        };

        $user = new User();

        $owner = new User();
        $owner->setName('sarah');
        CurrentUser::set($owner);
        $user->setOwner($owner);

        CurrentUser::set(null);
        self::assertFalse($acl->isCurrentUserAllowed($user, 'update'), 'anonymous cannot update');
        self::assertSame('Non-logged user with role anonymous is not allowed on resource "User#null" with privilege "update"', $acl->getLastDenialMessage());

        CurrentUser::set($owner);
        self::assertFalse($acl->isCurrentUserAllowed($user, 'update'), 'student cannot update even if owner');
        self::assertSame('User "sarah" with role member is not allowed on resource "User#null" with privilege "update" because it is not himself', $acl->getLastDenialMessage());

        $other = new User();
        $other->setName('john');
        CurrentUser::set($other);
        self::assertFalse($acl->isCurrentUserAllowed($user, 'update'), 'other user cannot update');
        self::assertSame('User "john" with role member is not allowed on resource "User#null" with privilege "update" because it is not himself', $acl->getLastDenialMessage());

        // Test again the first case to assert that reject reason does not leak from one assertion to the next
        CurrentUser::set(null);
        self::assertFalse($acl->isCurrentUserAllowed($user, 'update'), 'anonymous cannot update');
        self::assertSame('Non-logged user with role anonymous is not allowed on resource "User#null" with privilege "update"', $acl->getLastDenialMessage());
    }

    public function testMultipleReasons(): void
    {
        $acl = new class() extends Acl {
            public function __construct()
            {
                $user = $this->createModelResource(User::class);
                $this->addRole('anonymous');
                $this->addRole('member', 'anonymous');
                $this->allow(
                    'anonymous',
                    [$user],
                    ['update'],
                    new class() implements AssertionInterface {
                        /**
                         * @param Acl $acl
                         * @param null|mixed $privilege
                         */
                        public function assert(\Laminas\Permissions\Acl\Acl $acl, ?RoleInterface $role = null, ?ResourceInterface $resource = null, $privilege = null)
                        {
                            return $acl->reject('mocked reason');
                        }
                    }
                );
                $this->allow('member', [$user], ['update'], new IsMyself());
            }
        };

        $user = new User();
        $user->setName('sarah');
        CurrentUser::set($user);

        self::assertFalse($acl->isCurrentUserAllowed(new User(), 'update'), 'student cannot update even if user');
        $expected = <<<STRING
            User "sarah" with role member is not allowed on resource "User#null" with privilege "update" because:

            - it is not himself
            - mocked reason
            STRING;
        self::assertSame($expected, $acl->getLastDenialMessage());
    }

    public function testMultipleRoles(): void
    {
        $acl = new class() extends Acl {
            public function __construct()
            {
                $user = $this->createModelResource(User::class);
                $this->addRole('reader');
                $this->addRole('writer');
                $this->allow('writer', [$user], ['update']);
            }
        };

        CurrentUser::set(new User(new MultipleRoles()));
        self::assertFalse($acl->isCurrentUserAllowed(new User(), 'update'));
        self::assertSame('User "" with role [] is not allowed on resource "User#null" with privilege "update"', $acl->getLastDenialMessage());

        CurrentUser::set(new User(new MultipleRoles(['reader'])));
        self::assertFalse($acl->isCurrentUserAllowed(new User(), 'update'));
        self::assertSame('User "" with role [reader] is not allowed on resource "User#null" with privilege "update"', $acl->getLastDenialMessage());

        CurrentUser::set(new User(new MultipleRoles(['reader', 'writer'])));
        self::assertTrue($acl->isCurrentUserAllowed(new User(), 'update'));
        self::assertNull($acl->getLastDenialMessage());

        self::assertFalse($acl->isAllowed(new MultipleRoles(), User::class, 'update'));
        self::assertFalse($acl->isAllowed(new MultipleRoles(['reader']), User::class, 'update'));
        self::assertTrue($acl->isAllowed(new MultipleRoles(['reader', 'writer']), User::class, 'update'));
    }
}
