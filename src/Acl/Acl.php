<?php

declare(strict_types=1);

namespace Ecodev\Felix\Acl;

use Doctrine\Common\Util\ClassUtils;
use Ecodev\Felix\Model\CurrentUser;
use Ecodev\Felix\Model\Model;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

class Acl extends \Laminas\Permissions\Acl\Acl
{
    /**
     * The message explaining the last denial.
     */
    private ?string $message = null;

    /**
     * @var string[]
     */
    private array $reasons = [];

    private DebugAcl $debugAcl;

    public function __construct()
    {
        $this->debugAcl = new DebugAcl();
    }

    public function addRole($role, $parents = null)
    {
        $this->debugAcl->addRole($role, $parents);

        return parent::addRole($role, $parents);
    }

    public function addResource($resource, $parent = null)
    {
        $this->debugAcl->addResource($resource, $parent);

        return parent::addResource($resource, $parent);
    }

    public function allow($roles = null, $resources = null, $privileges = null, ?AssertionInterface $assert = null)
    {
        $this->debugAcl->allow($roles, $resources, $privileges, $assert);

        return parent::allow($roles, $resources, $privileges, $assert);
    }

    public function deny($roles = null, $resources = null, $privileges = null, ?AssertionInterface $assert = null)
    {
        $this->debugAcl->deny($roles, $resources, $privileges, $assert);

        return parent::deny($roles, $resources, $privileges, $assert);
    }

    protected function createModelResource(string $class): ModelResource
    {
        $resource = new ModelResource($class);
        $this->addResource($resource);

        return $resource;
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
     * Return whether the current user is allowed to do something.
     *
     * This should be the main method to do all ACL checks.
     */
    public function isCurrentUserAllowed(Model $model, string $privilege): bool
    {
        $resource = new ModelResource($this->getClass($model), $model);
        $role = $this->getCurrentRole();
        $this->reasons = [];

        $isAllowed = $this->isAllowed($role, $resource, $privilege);

        $this->message = $this->buildMessage($resource, $privilege, $role, $isAllowed);

        return $isAllowed;
    }

    /**
     * Set the reason for rejection that will be shown to end-user.
     *
     * This method always return false for usage convenience and should be used by all assertions,
     * instead of only return false themselves.
     *
     * @return false
     */
    public function reject(string $reason): bool
    {
        $this->reasons[] = $reason;

        return false;
    }

    private function getClass(Model $resource): string
    {
        return ClassUtils::getRealClass($resource::class);
    }

    private function getCurrentRole(): MultipleRoles|string
    {
        $user = CurrentUser::get();
        if (!$user) {
            return 'anonymous';
        }

        return $user->getRole();
    }

    private function buildMessage(ModelResource $resource, ?string $privilege, MultipleRoles|string $role, bool $isAllowed): ?string
    {
        if ($isAllowed) {
            return null;
        }

        $resource = $resource->getName();

        $user = CurrentUser::get();
        $userName = $user ? 'User "' . $user->getLogin() . '"' : 'Non-logged user';
        $privilege ??= 'NULL';

        $message = "$userName with role $role is not allowed on resource \"$resource\" with privilege \"$privilege\"";

        $count = count($this->reasons);
        if ($count === 1) {
            $message .= ' because ' . $this->reasons[0];
        } elseif ($count) {
            $list = array_map(fn ($reason) => '- ' . $reason, $this->reasons);
            $message .= ' because:' . PHP_EOL . PHP_EOL . implode(PHP_EOL, $list);
        }

        return $message;
    }

    /**
     * Returns the message explaining the last denial, if any.
     */
    public function getLastDenialMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @return array<array{resource:string, privileges: array<int, array{privilege:null|string, allowed: bool, allowIf: string[], denyIf: string[]}>}>
     */
    public function show(MultipleRoles|string $role): array
    {
        $result = [];
        /** @var string[] $resources */
        $resources = $this->getResources();
        sort($resources);

        foreach ($resources as $resource) {
            $privileges = [];
            foreach ($this->debugAcl->getPrivileges() as $privilege) {
                $privileges[] = $this->debugAcl->show($role, $resource, $privilege);
            }

            $result[] = [
                'resource' => $resource,
                'privileges' => $privileges,
            ];
        }

        ksort($result);

        return $result;
    }
}
