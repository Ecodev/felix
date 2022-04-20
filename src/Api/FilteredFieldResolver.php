<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\Proxy;
use GraphQL\Doctrine\DefaultFieldResolver;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * A field resolver that will ensure that filtered entity are never returned via getter.
 */
final class FilteredFieldResolver
{
    private readonly DefaultFieldResolver $resolver;

    public function __construct()
    {
        $this->resolver = new DefaultFieldResolver();
    }

    /**
     * @param mixed[] $args
     */
    public function __invoke(mixed $source, array $args, mixed $context, ResolveInfo $info): mixed
    {
        $value = $this->resolver->__invoke($source, $args, $context, $info);

        return $this->load($value);
    }

    /**
     * Try to load the entity from DB, but if it is filtered, it will return null.
     *
     * This mechanic is necessary to hide entities that should have been filtered by
     * AclFilter, but that are accessed via lazy-loaded by doctrine on a *-to-one relation.
     * This scenario is described in details on https://github.com/doctrine/doctrine2/issues/4543
     *
     * @param mixed $object object or any kind of value
     */
    private function load(mixed $object): mixed
    {
        if ($object instanceof Proxy) {
            try {
                $object->__load();
            } catch (EntityNotFoundException) {
                return null;
            }
        }

        return $object;
    }
}
