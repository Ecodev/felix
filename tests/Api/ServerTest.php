<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Api;

use Ecodev\Felix\Api\Server;
use EcodevTests\Felix\Traits\TestWithContainer;
use Exception;
use GraphQL\Error\UserError;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Diactoros\CallbackStream;
use Laminas\Diactoros\ServerRequest;
use Laminas\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    use TestWithContainer;

    /**
     * @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $aggregator = new ConfigAggregator([
            new ArrayProvider([
                'dependencies' => [
                    'factories' => [
                        LoggerInterface::class => fn () => $this->logger,
                    ],
                ],
            ]),
        ]);

        $this->createContainer($aggregator);
    }

    /**
     * @dataProvider providerExecute
     */
    public function testExecute(string $body, array $expected, string $expectedLog = ''): void
    {
        $schema = new Schema(['query' => new ObjectType([
            'name' => 'Query',
            'fields' => [
                'nativeException' => [
                    'type' => Type::boolean(),
                    'resolve' => fn () => throw new Exception('Fake message'),
                ],
                'felixException' => [
                    'type' => Type::boolean(),
                    'resolve' => fn () => throw new \Ecodev\Felix\Api\Exception('Fake message'),
                ],
                'notFoundException' => [
                    'type' => Type::boolean(),
                    'resolve' => fn () => throw new UserError('Entity not found for class `foo` and ID `bar`.'),
                ],
                'invalidVariables' => [
                    'args' => [
                        'myArg' => Type::boolean(),
                    ],
                    'type' => Type::boolean(),
                    'resolve' => fn () => true,
                ],
            ],
        ])]);

        if ($expectedLog) {
            $this->logger->expects(self::once())->method('err')->with($expectedLog);
        } else {
            $this->logger->expects(self::never())->method('err');
        }

        $server = new Server($schema, false);
        $request = new ServerRequest(method: 'POST', body: new CallbackStream(fn () => $body));
        $request = $request->withHeader('content-type', 'application/json');

        $result = $server->execute($request);

        self::assertInstanceOf(ExecutionResult::class, $result);
        self::assertSame($expected, $result->jsonSerialize());
    }

    public static function providerExecute(): iterable
    {
        yield 'empty body' => [
            '',
            [
                'errors' => [
                    [
                        'message' => 'GraphQL Request must include at least one of those two parameters: "query" or "queryId"',
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

        yield 'native exception' => [
            '{"query": "{ nativeException }"}',
            [
                'errors' => [
                    [
                        'message' => 'Internal server error',
                        'locations' => [
                            [
                                'line' => 1,
                                'column' => 3,
                            ],
                        ],
                        'path' => [
                            'nativeException',
                        ],
                    ],
                ],
                'data' => [
                    'nativeException' => null,
                ],
            ],
            <<<STRING
                Fake message

                GraphQL request (1:3)
                1: { nativeException }
                     ^

                STRING
        ];

        yield 'Felix exception shows snackbar' => [
            '{"query": "{ felixException }"}',
            [
                'errors' => [
                    [
                        'message' => 'Fake message',
                        'locations' => [
                            [
                                'line' => 1,
                                'column' => 3,
                            ],
                        ],
                        'path' => [
                            'felixException',
                        ],
                        'extensions' => [
                            'showSnack' => true,
                        ],
                    ],
                ],
                'data' => [
                    'felixException' => null,
                ],
            ],
            <<<STRING
                Fake message

                GraphQL request (1:3)
                1: { felixException }
                     ^

                STRING
        ];

        yield 'not found exception does not show snackbar but is flagged' => [
            '{"query": "{ notFoundException }"}',
            [
                'errors' => [
                    [
                        'message' => 'Entity not found for class `foo` and ID `bar`.',
                        'locations' => [
                            [
                                'line' => 1,
                                'column' => 3,
                            ],
                        ],
                        'path' => [
                            'notFoundException',
                        ],
                        'extensions' => [
                            'objectNotFound' => true,
                        ],
                    ],
                ],
                'data' => [
                    'notFoundException' => null,
                ],
            ],
            <<<STRING
                Entity not found for class `foo` and ID `bar`.

                GraphQL request (1:3)
                1: { notFoundException }
                     ^

                STRING
        ];

        yield 'invalidVariables shows snackbar' => [
            '{"query": "query ($v: Boolean) { invalidVariables(myArg: $v) }", "variables": {"v": 123}}',
            [
                'errors' => [
                    [
                        'message' => 'Variable "$v" got invalid value 123; Boolean cannot represent a non boolean value: 123',
                        'locations' => [
                            [
                                'line' => 1,
                                'column' => 8,
                            ],
                        ],
                        'extensions' => [
                            'showSnack' => true,
                        ],
                    ],
                ],
            ],
            <<<STRING
                Variable "\$v" got invalid value 123; Boolean cannot represent a non boolean value: 123

                GraphQL request (1:8)
                1: query (\$v: Boolean) { invalidVariables(myArg: \$v) }
                          ^

                STRING
        ];
    }
}
