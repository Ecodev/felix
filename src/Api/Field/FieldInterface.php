<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Field;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use Mezzio\Session\SessionInterface;

/**
 * Represent a single field configuration.
 *
 * Below, we re-define some type coming from webonyx/graphql, because our `\GraphQL\Doctrine\Types::get()`
 * is too poorly typed to match the correctness of webonyx/graphql. If we are able to fix our typing one day,
 * then we should remove those overrides below.
 *
 * @phpstan-import-type FieldType from FieldDefinition
 * @phpstan-import-type ComplexityFn from FieldDefinition
 * @phpstan-import-type VisibilityFn from FieldDefinition
 *
 * @phpstan-type PermissiveArgumentListConfig iterable<mixed>
 * @phpstan-type FieldResolverWithSessionInterface callable(mixed, array<string, mixed>, SessionInterface, ResolveInfo): mixed
 * @phpstan-type PermissiveUnnamedFieldDefinitionConfig array{
 *      type: FieldType,
 *      resolve?: FieldResolverWithSessionInterface|null,
 *      args?: PermissiveArgumentListConfig|null,
 *      description?: string|null,
 *      visible?: VisibilityFn|bool,
 *      deprecationReason?: string|null,
 *      astNode?: FieldDefinitionNode|null,
 *      complexity?: ComplexityFn|null
 *  }
 * @phpstan-type PermissiveDefinitionResolver callable(): PermissiveUnnamedFieldDefinitionConfig
 * @phpstan-type PermissiveFieldsConfig iterable<string, PermissiveDefinitionResolver>
 */
interface FieldInterface
{
    /**
     * Return the single field configuration, including its name.
     *
     * @return PermissiveFieldsConfig
     */
    public static function build(): iterable;
}
