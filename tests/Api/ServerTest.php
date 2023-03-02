<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api;

use Ecodev\Felix\Api\Server;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use Laminas\Diactoros\CallbackStream;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    /**
     * @dataProvider providerExecute
     */
    public function testExecute(string $body, array $expected): void
    {
        $schema = new Schema(['query' => new ObjectType(['name' => 'Query'])]);
        $server = new Server($schema, false);
        $request = new ServerRequest(method: 'POST', body: new CallbackStream(fn () => $body));
        $request = $request->withHeader('content-type', 'application/json');

        $result = $server->execute($request);

        self::assertInstanceOf(ExecutionResult::class, $result);
        self::assertSame($expected, $result->jsonSerialize());
    }

    public function providerExecute(): iterable
    {
        yield 'empty body' => [
            '',
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                            'category' => 'request',
                        ],
                    ],
                ],
            ],
        ];

        yield 'invalid json' => [
            'foo bar',
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                            'category' => 'request',
                        ],
                    ],
                ],
            ],
        ];

        yield 'empty query' => [
            '{"query": ""}',
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
                        'extensions' => [
                            'category' => 'request',
                        ],
                    ],
                ],
            ],
        ];

        yield 'normal' => [
            '{"query": "{ __typename }"}',
            [
                'data' => [
                    '__typename' => 'Query',
                ],
            ],
        ];
    }
}
