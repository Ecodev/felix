<?php

declare(strict_types=1);

namespace Ecodev\Felix\Acl;

use Doctrine\Common\Util\ClassUtils;
use Ecodev\Felix\Model\CurrentUser;
use Ecodev\Felix\Model\Model;
use Exception;
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

    /**
     * @var array<string, string>
     */
    private array $resourceTranslations = [];

    /**
     * @var array<string, string>
     */
    private array $privilegeTranslations = [];

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
     * @param null|RoleInterface|string $role
     * @param null|ResourceInterface|string $resource
     * @param null|string $privilege
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
    public function isCurrentUserAllowed(Model|string $modelOrResource, string $privilege): bool
    {
        $resource = is_string($modelOrResource) ? $modelOrResource : new ModelResource($this->getClass($modelOrResource), $modelOrResource);
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

    private function buildMessage(ModelResource|string $resource, ?string $privilege, MultipleRoles|string $role, bool $isAllowed): ?string
    {
        if ($isAllowed) {
            return null;
        }

        if ($resource instanceof ModelResource) {
            $resource = $resource->getName();
        }

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
     * Show the ACL configuration for the given role in a structured array.
     *
     * @return array<array{resource:string, privileges: array<int, array{privilege:null|string, allowed: bool, allowIf: string[], denyIf: string[]}>}>
     */
    public function show(MultipleRoles|string $role, bool $useTranslations = true): array
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

        if ($useTranslations && ($this->resourceTranslations || $this->privilegeTranslations)) {
            foreach ($result as &$resource) {
                $resource['resource'] = $this->translate($this->resourceTranslations, $resource['resource']);
                foreach ($resource['privileges'] as &$privilege) {
                    $privilege['privilege'] = $this->translate($this->privilegeTranslations, $privilege['privilege'] ?? '');
                }
            }
        }

        return $result;
    }

    /**
     * Configure the translations for all resources and all privileges for the `show()` method.
     *
     * If this is set but one translation is missing, then `show()` will throw an exception when called.
     *
     * To disable translation altogether you can pass two empty lists.
     *
     * @param array<string, string> $resourceTranslations
     * @param array<string, string> $privilegeTranslations
     */
    public function setTranslations(array $resourceTranslations, array $privilegeTranslations): void
    {
        $this->resourceTranslations = $resourceTranslations;
        $this->privilegeTranslations = $privilegeTranslations;
    }

    /**
     * @param array<string, string> $translations
     */
    private function translate(array $translations, string $message): string
    {
        return $translations[$message] ?? throw new Exception('Was not marked as translatable: ' . $message);
    }

    /**
     * Returns all non-null privileges indexed by all non-null resources.
     *
     * @return array<string, array<string>>
     */
    public function getPrivilegesByResource(): array
    {
        return $this->debugAcl->getPrivilegesByResource();
    }
}
