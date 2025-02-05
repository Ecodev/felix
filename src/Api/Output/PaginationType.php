<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Output;

use Ecodev\Felix\Model\Model;
use GraphQL\Type\Definition\ObjectType;

final class PaginationType extends ObjectType
{
    /**
     * PaginationType constructor.
     *
     * @param class-string $class
     */
    public function __construct(string $class, string $name, array $extraFields)
    {
        $config = [
            'name' => $name,
            'description' => 'Describe available pages',
            'fields' => function () use ($class, $extraFields): array {
                $itemType = is_a($class, Model::class, true) ? _types()->getOutput($class) : _types()->get($class);

                $fields = [
                    'offset' => [
                        'type' => self::int(),
                        'description' => 'The zero-based index of the displayed list of items',
                    ],
                    'pageIndex' => [
                        'type' => self::nonNull(self::int()),
                        'description' => 'The zero-based page index of the displayed list of items',
                    ],
                    'pageSize' => [
                        'type' => self::nonNull(self::int()),
                        'description' => 'Number of items to display on a page',
                    ],
                    'length' => [
                        'type' => self::nonNull(self::int()),
                        'description' => 'The length of the total number of items that are being paginated',
                    ],
                    'items' => [
                        'type' => self::nonNull(self::listOf(self::nonNull($itemType))),
                        'description' => 'Paginated items',
                    ],
                ];

                $fields = array_merge($fields, $extraFields);

                return $fields;
            },
        ];

        parent::__construct($config);
    }
}
