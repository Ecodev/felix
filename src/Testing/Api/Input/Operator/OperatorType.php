<?php

declare(strict_types=1);

namespace Ecodev\Felix\Testing\Api\Input\Operator;

use GraphQL\Doctrine\Definition\EntityID;
use PHPUnit\Framework\TestCase;

class OperatorType extends TestCase
{
    private function getFilter(string $field, string $operator, array $values): array
    {
        return [
            'groups' => [
                [
                    'groupLogic' => 'AND',
                    'conditionsLogic' => 'AND',
                    'conditions' => [
                        [
                            $field => [$operator => $values],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param class-string $class
     */
    protected function getFilteredResult(string $class, string $field, string $operator, array $values): array
    {
        $filter = $this->getFilter($field, $operator, $values);
        $qb = _types()->createFilteredQueryBuilder($class, $filter, []);

        // @phpstan-ignore-next-line
        return $qb->getQuery()->getResult();
    }

    /**
     * Parse an array of ID into an array of EntityID.
     *
     * @param class-string $entity
     * @param null|int[] $ids
     *
     * @return ($ids is null ? null : EntityID[])
     */
    protected function idsToEntityIds(string $entity, ?array $ids): ?array
    {
        $type = _types()->getId($entity);
        $parsed = null;
        if ($ids !== null) {
            $parsed = [];
            foreach ($ids as $id) {
                $parsed[] = $type->parseValue($id);
            }
        }

        return $parsed;
    }
}
