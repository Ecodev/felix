<?php

declare(strict_types=1);

namespace Ecodev\Felix\Acl;

use Ecodev\Felix\Acl\Assertion\NamedAssertion;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Assertion\CallbackAssertion;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * Debug ACL is used to mirror normal ACL but with assertions configured but disabled. This allows
 * to query the entire ACL configuration, including configured assertions, while still leveraging
 * the complex underlying logic of Laminas ACL.
 *
 * @internal
 */
final class DebugAcl extends \Laminas\Permissions\Acl\Acl
{
    /**
     * @var string[]
     */
    private array $usedAllowAssertions = [];

    /**
     * @var string[]
     */
    private array $usedDenyAssertions = [];

    /**
     * @var array<null|string> All possible privileges
     */
    private array $privileges = [];

    /**
     * @var array<string, array<string>> All possible privileges
     */
    private array $privilegesByResource = [];

    private function wrapAssertion(?AssertionInterface $assert, bool $isAllow): ?AssertionInterface
    {
        if (!$assert) {
            return null;
        }

        return new CallbackAssertion(function () use ($assert, $isAllow) {
            $name = $assert instanceof NamedAssertion ? $assert->getName() : 'unnamed assertion ' . $assert::class;

            if ($isAllow) {
                $this->usedAllowAssertions[] = $name;
            } else {
                $this->usedDenyAssertions[] = $name;
            }

            // hardcode response so other assertions from other roles are executed too
            return !$isAllow;
        });
    }

    public function allow($roles = null, $resources = null, $privileges = null, ?AssertionInterface $assert = null)
    {
        $this->storePrivileges($resources, $privileges);
        $assert = $this->wrapAssertion($assert, true);

        return parent::allow($roles, $resources, $privileges, $assert);
    }

    public function deny($roles = null, $resources = null, $privileges = null, ?AssertionInterface $assert = null)
    {
        $this->storePrivileges($resources, $privileges);
        $assert = $this->wrapAssertion($assert, false);

        return parent::deny($roles, $resources, $privileges, $assert);
    }

    private function storePrivileges(null|string|array|ResourceInterface $resource, null|string|array $privileges): void
    {
        if (!is_array($resource)) {
            $resource = [$resource];
        }

        if (!is_array($privileges)) {
            $privileges = [$privileges];
        }

        $this->privileges = array_merge($this->privileges, $privileges);

        // Keep non-null privileges only
        $privileges = array_filter($privileges);
        if (!$privileges) {
            return;
        }

        foreach ($resource as $oneResource) {
            $oneResource = (string) $oneResource;
            if (!$oneResource) {
                continue;
            }

            if (!isset($this->privilegesByResource[$oneResource])) {
                $this->privilegesByResource[$oneResource] = [];
            }

            $this->privilegesByResource[$oneResource] = array_merge($this->privilegesByResource[$oneResource], $privileges);
        }
    }

    /**
     * Returns all non-null privileges indexed by all non-null resources.
     *
     * @return array<string, array<string>>
     */
    public function getPrivilegesByResource(): array
    {
        foreach ($this->privilegesByResource as &$privileges) {
            $privileges = array_unique($privileges);
            sort($privileges);
        }

        return $this->privilegesByResource;
    }

    /**
     * @return array<null|string>
     */
    public function getPrivileges(): array
    {
        // Keep most common privileges at the beginning, for convenience
        $mostCommon = [
            null,
            'create',
            'read',
            'update',
            'index',
            'view',
            'edit',
            'add',
            'delete',
            'deleteAll',
        ];

        $mostCommonThatExists = array_unique(array_intersect($mostCommon, $this->privileges));
        $result = array_unique(array_diff($this->privileges, $mostCommonThatExists));
        sort($result);

        $result = array_merge($mostCommonThatExists, $result);

        return $result;
    }

    /**
     * Override parent to provide compatibility with MultipleRoles.
     *
     * @param RoleInterface|string $role
     * @param ResourceInterface|string $resource
     * @param ?string $privilege
     */
    public function isAllowed($role = null, $resource = null, $privilege = null): bool
    {
        $this->usedAllowAssertions = [];
        $this->usedDenyAssertions = [];

        // Normalize roles
        if ($role instanceof MultipleRoles) {
            $roles = $role->getRoles();
        } else {
            $roles = [$role];
        }

        // If at least one role is allowed, then return early
        foreach ($roles as $role) {
            if (parent::isAllowed($role, $resource, $privilege)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the privilege is allowed and the assertions that were used to determine that.
     *
     * @return array{privilege: null|string, allowed: bool, allowIf: string[], denyIf: string[]}
     */
    public function show(null|RoleInterface|string $role, null|ResourceInterface|string $resource, null|string $privilege): array
    {
        $allowed = $this->isAllowed($role, $resource, $privilege);
        sort($this->usedAllowAssertions);
        sort($this->usedDenyAssertions);

        return [
            'privilege' => $privilege,
            'allowed' => $allowed,
            'allowIf' => array_unique($this->usedAllowAssertions),
            'denyIf' => array_unique($this->usedDenyAssertions),
        ];
    }
}
