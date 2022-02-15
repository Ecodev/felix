<?php

declare(strict_types=1);

namespace Ecodev\Felix\Testing\Api\Input\Sorting;

use Ecodev\Felix\Model\Model;
use GraphQL\Doctrine\Types;
use PHPUnit\Framework\TestCase;

abstract class AbstractSorting extends TestCase
{
    /**
     * @param class-string $class
     */
    protected function getSortedQueryResult(Types $types, string $class, string $field): array
    {
        $sorting = $this->getSorting($field);
        $qb = $types->createFilteredQueryBuilder($class, [], $sorting);

        $result = [];
        /** @var Model[] $items */
        $items = $qb->getQuery()->getResult();
        foreach ($items as $item) {
            $result[] = $item->getId();
        }

        return $result;
    }

    private function getSorting(string $field): array
    {
        return [
            [
                'field' => $field,
                'order' => 'DESC',
            ],
            [
                'field' => 'id',
                'order' => 'ASC',
            ],
        ];
    }
}
