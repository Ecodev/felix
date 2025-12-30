<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api;

use ArrayObject;
use Doctrine\ORM\EntityNotFoundException;
use Ecodev\Felix\Api\FilteredFieldResolver;
use EcodevTests\Felix\Blog\Model\User;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

final class FilteredFieldResolverTest extends TestCase
{
    #[DataProvider('providerLoad')]
    public function testLoad(mixed $value, mixed $expected): void
    {
        $model = new class($value) {
            public function __construct(
                private mixed $value,
            ) {}

            public function getField(): mixed
            {
                return $this->value;
            }
        };

        $fieldDefinition = new FieldDefinition(['name' => 'field', 'type' => Type::boolean()]);
        $resolve = new ResolveInfo($fieldDefinition, new ArrayObject(), new ObjectType(['name' => 'foo', 'fields' => []]), [], new Schema([]), [], null, new OperationDefinitionNode([]), []);
        $resolver = new FilteredFieldResolver();
        self::assertSame($expected, $resolver($model, [], [], $resolve));
    }

    public static function providerLoad(): iterable
    {
        $reflector = new ReflectionClass(self::class);

        $loadable = $reflector->newLazyGhost(fn () => null); // nothing to initialize
        $unloadable = $reflector->newLazyGhost(fn () => throw new EntityNotFoundException());

        $object = new stdClass();
        $user = new User();

        return [
            [null, null],
            [1, 1],
            ['foo', 'foo'],
            [$object, $object],
            [$user, $user],
            [$loadable, $loadable],
            [$unloadable, null],
        ];
    }
}
