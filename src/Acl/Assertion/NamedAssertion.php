<?php

declare(strict_types=1);

namespace Ecodev\Felix\Acl\Assertion;

use Laminas\Permissions\Acl\Assertion\AssertionInterface;

interface NamedAssertion extends AssertionInterface
{
    public function getName(): string;
}
