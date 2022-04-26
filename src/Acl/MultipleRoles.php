<?php

declare(strict_types=1);

namespace Ecodev\Felix\Acl;

use ArrayIterator;
use Exception;
use IteratorAggregate;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Stringable;
use Traversable;

/**
 * A role containing multiple roles.
 */
class MultipleRoles implements IteratorAggregate, RoleInterface, Stringable
{
    /**
     * @var string[]
     */
    private array $roles = [];

    /**
     * @param string[] $roles
     */
    public function __construct(array $roles = [])
    {
        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    /**
     * Add a role to the list.
     */
    public function addRole(string $role): void
    {
        $this->roles[] = $role;

        $this->roles = array_unique($this->roles);
        sort($this->roles);
    }

    public function getRoleId(): never
    {
        throw new Exception('This should never be called. If it is, then it means this class is not used correctly');
    }

    /**
     * Return the role list.
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Return whether at least one of the given role is included in the list.
     */
    public function has(string ...$roles): bool
    {
        return (bool) array_intersect($this->roles, $roles);
    }

    public function __toString(): string
    {
        return '[' . implode(', ', $this->roles) . ']';
    }

    /**
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->roles);
    }
}
