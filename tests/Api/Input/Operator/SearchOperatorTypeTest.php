<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api\Input\Operator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Ecodev\Felix\Api\Exception;
use Ecodev\Felix\Api\Input\Operator\SearchOperatorType;
use Ecodev\Felix\Testing\Api\Input\Operator\OperatorType;
use EcodevTests\Felix\Blog\Model\Post;
use EcodevTests\Felix\Blog\Model\User;
use EcodevTests\Felix\Traits\TestWithTypes;
use GraphQL\Doctrine\Factory\UniqueNameFactory;
use GraphQL\Type\Definition\Type;

final class SearchOperatorTypeTest extends OperatorType
{
    use TestWithTypes;

    /**
     * @dataProvider providerSearch
     */
    public function testSearch(string $class, string $term, int $expectedJoinCount, ?string $expected): void
    {
        $operator = new class($this->types, Type::string()) extends SearchOperatorType {
            protected function getSearchableFieldsWhitelist(ClassMetadata $metadata): array
            {
                return ['name', 'email', 'title'];
            }

            protected function getSearchableJoinedEntities(): array
            {
                return [Post::class => ['user']];
            }
        };

        $metadata = $this->entityManager->getClassMetadata($class);
        $unique = new UniqueNameFactory();
        $alias = 'a';
        $qb = $this->entityManager->getRepository($class)->createQueryBuilder($alias);
        $actual = $operator->getDqlCondition($unique, $metadata, $qb, $alias, 'non-used-field-name', ['value' => $term]);

        self::assertSame($expected, $actual);

        $joins = $qb->getDQLPart('join');
        self::assertCount($expectedJoinCount, $joins['a'] ?? []);
    }

    public function providerSearch(): array
    {
        return [
            'empty term' => [
                User::class,
                '',
                0,
                null,
            ],
            'only whitespace is dropped' => [
                User::class,
                '    ',
                0,
                null,
            ],
            'quoted whitespace is kept' => [
                User::class,
                '"    "',
                0,
                '(a.name LIKE :filter1 OR a.email LIKE :filter1)',
            ],
            'empty quoted term' => [
                User::class,
                '""',
                0,
                null,
            ],
            'mixed empty term' => [
                User::class,
                '   ""  ""  ',
                0,
                null,
            ],
            'search predefined fields' => [
                User::class,
                'john',
                0,
                '(a.name LIKE :filter1 OR a.email LIKE :filter1)',
            ],
            'split words' => [
                User::class,
                'john doe',
                0,
                '(a.name LIKE :filter1 OR a.email LIKE :filter1) AND (a.name LIKE :filter2 OR a.email LIKE :filter2)',
            ],
            'quoted words are not split' => [
                User::class,
                '"john doe"',
                0,
                '(a.name LIKE :filter1 OR a.email LIKE :filter1)',
            ],
            'trimmed split words' => [
                User::class,
                '  foo   bar   ',
                0,
                '(a.name LIKE :filter1 OR a.email LIKE :filter1) AND (a.name LIKE :filter2 OR a.email LIKE :filter2)',
            ],
            'mixed quoted and non-quoted' => [
                User::class,
                ' a b "john doe" c d e " f g h i j k l m n o p q r s t u v w x y z "  ',
                0,
                '(a.name LIKE :filter1 OR a.email LIKE :filter1) AND (a.name LIKE :filter2 OR a.email LIKE :filter2) AND (a.name LIKE :filter3 OR a.email LIKE :filter3) AND (a.name LIKE :filter4 OR a.email LIKE :filter4) AND (a.name LIKE :filter5 OR a.email LIKE :filter5) AND (a.name LIKE :filter6 OR a.email LIKE :filter6) AND (a.name LIKE :filter7 OR a.email LIKE :filter7)',
            ],
            'no duplicates' => [
                User::class,
                'dup "dup" dup "dup"',
                0,
                '(a.name LIKE :filter1 OR a.email LIKE :filter1)',
            ],
            'joined entities' => [
                Post::class,
                'foo',
                1,
                '(a.title LIKE :filter1 OR user1.name LIKE :filter1 OR user1.email LIKE :filter1)',
            ],
        ];
    }

    public function testSearchOnEntityWithoutSearchableFieldMustThrow(): void
    {
        $operator = new class($this->types, Type::string()) extends SearchOperatorType {
            protected function getSearchableFieldsWhitelist(ClassMetadata $metadata): array
            {
                return [];
            }

            protected function getSearchableJoinedEntities(): array
            {
                return [];
            }
        };

        $metadata = $this->entityManager->getClassMetadata(User::class);
        $unique = new UniqueNameFactory();
        $alias = 'a';
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder($alias);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot find fields to search on for entity EcodevTests\Felix\Blog\Model\User');
        $operator->getDqlCondition($unique, $metadata, $qb, $alias, 'non-used-field-name', ['value' => 'foo']);
    }
}
