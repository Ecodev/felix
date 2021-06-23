<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Input;

use GraphQL\Doctrine\Types;
use GraphQL\Type\Definition\InputObjectType;

final class PaginationInputType extends InputObjectType
{
    public static function build(Types $types): array
    {
        return [
            'name' => 'pagination',
            'type' => $types->get(self::class),
            'defaultValue' => [
                'offset' => null,
                'pageIndex' => 0,
                'pageSize' => 50,
            ],
        ];
    }

    public function __construct()
    {
        $config = [
            'description' => 'Describe what page we want',
            'fields' => function (): array {
                return [
                    'offset' => [
                        'type' => self::int(),
                        'defaultValue' => null,
                        'description' => 'The zero-based index of first item of the page. If given a value greater than zero, then `pageIndex` is ignored.',
                    ],
                    'pageIndex' => [
                        'type' => self::int(),
                        'defaultValue' => 0,
                        'description' => 'The zero-based index of the page. If given negative value, then fallback to 0.',
                    ],
                    'pageSize' => [
                        'type' => self::int(),
                        'defaultValue' => 50,
                        'description' => 'Number of items to display on a page. If given negative value, then fallback to 0.',
                    ],
                ];
            },
        ];

        parent::__construct($config);
    }
}
