<?php

declare(strict_types=1);

namespace Ecodev\Felix\Acl\Assertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;

final class All implements NamedAssertion
{
    /**
     * @var NamedAssertion[]
     */
    private readonly array $asserts;

    /**
     * Check if all asserts are true.
     */
    public function __construct(NamedAssertion ...$asserts)
    {
        $this->asserts = $asserts;
    }

    /**
     * Assert that all given asserts are correct (AND logic).
     *
     * @param \Ecodev\Felix\Acl\Acl $acl
     * @param ?string $privilege
     */
    public function assert(Acl $acl, ?RoleInterface $role = null, ?ResourceInterface $resource = null, $privilege = null): bool
    {
        foreach ($this->asserts as $assert) {
            if (!$assert->assert($acl, $role, $resource, $privilege)) {
                return false;
            }
        }

        return true;
    }

    public function getName(): string
    {
        return implode(_tr(', et '), array_map(fn ($a) => $a->getName(), $this->asserts));
    }
}
