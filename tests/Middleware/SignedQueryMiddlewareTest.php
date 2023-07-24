<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Middleware;

use Cake\Chronos\Chronos;
use Ecodev\Felix\Middleware\SignedQueryMiddleware;
use Laminas\Diactoros\CallbackStream;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class SignedQueryMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        Chronos::setTestNow((new Chronos('2020-01-02T12:30', 'Europe/Zurich')));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    /**
     * @dataProvider dataProviderQuery
     */
    public function testRequiredSignedQuery(array $keys, string $body, null|array $parsedBody, string $signature, string $expectExceptionMessage = ''): void
    {
        $this->process($keys, true, $body, $parsedBody, $signature, $expectExceptionMessage);
    }

    /**
     * @dataProvider dataProviderQuery
     */
    public function testNonRequiredSignedQuery(array $keys, string $body, null|array $parsedBody, string $signature): void
    {
        $this->process($keys, false, $body, $parsedBody, $signature, '');
    }

    public function testThrowIfNoKeys(): void
    {
        $this->expectExceptionMessage('Signed queries are required, but no keys are configured');
        $this->expectExceptionCode(0);
        new SignedQueryMiddleware([]);
    }

    private function process(array $keys, bool $required, string $body, null|array $parsedBody, string $signature, string $expectExceptionMessage): void
    {
        $request = new ServerRequest();
        $request = $request->withBody(new CallbackStream(fn () => $body))->withParsedBody($parsedBody);

        if ($signature) {
            $request = $request->withHeader('Authorization', $signature);
        }

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($expectExceptionMessage ? self::never() : self::once())
            ->method('handle')
            ->willReturn(new Response());

        $middleware = new SignedQueryMiddleware($keys, $required);

        if ($expectExceptionMessage) {
            $this->expectExceptionMessage($expectExceptionMessage);
            $this->expectExceptionCode(403);
        }

        $middleware->process($request, $handler);
    }

    public function dataProviderQuery(): iterable
    {
        $key1 = 'my-secret-1';
        $key2 = 'my-secret-2';

        yield 'simple' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577964600.a4d664cd3d9903e4fecf6f9f671ad953586a7faeb16e67c306fd9f29999dfdd7',
        ];

        yield 'simple but wrong key' => [
            [$key2],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577964600.a4d664cd3d9903e4fecf6f9f671ad953586a7faeb16e67c306fd9f29999dfdd7',
            'Invalid signed query',
        ];

        yield 'simple with all keys' => [
            [$key2, $key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577964600.a4d664cd3d9903e4fecf6f9f671ad953586a7faeb16e67c306fd9f29999dfdd7',
        ];

        yield 'simple but slightly in the past' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577951100.7d3b639703584e3ea4c68b30a37b56bcf94d19ccdc11c7f05a737c4e7e663a6c',
        ];

        yield 'simple but too much in the past' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577951099.' . str_repeat('a', 64),
            'Signed query is expired',
        ];

        yield 'simple but slightly in the future' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577978100.b6fb50cd1aa3974ec9df0c320bf32ff58a28f6fc2040aa13e529a7ef57212e49',
        ];

        yield 'simple but too much in the future' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577978101.' . str_repeat('a', 64),
            'Signed query is expired',
        ];

        yield 'batching' => [
            [$key1],
            '[{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }},{"operationName":"Configuration","variables":{"key":"announcement-active"},"query":"query Configuration($key: String!) { configuration(key: $key)}"}]',
            null,
            'v1.1577964600.566fafed794d956d662662b0df3d88e5c0a1e52e19111c08cc122f64a54bd8ec',

        ];

        yield 'file upload' => [
            [$key1],
            '',
            [
                'operations' => '{"operationName":"CreateImage","variables":{"input":{"file":null}},"query":"mutation CreateImage($input: ImageInput!) { createImage(input: $input) { id }}"}',
                'map' => '{"1":["variables.input.file"]}',
            ],
            'v1.1577964600.69dd1f396016e284afb221966ae5e61323a23222f2ad2a5086e4ba2354f99e58',
        ];

        yield 'file upload will ignore map and uploaded file to sign' => [
            [$key1],
            '',
            [
                'operations' => '{"operationName":"CreateImage","variables":{"input":{"file":null}},"query":"mutation CreateImage($input: ImageInput!) { createImage(input: $input) { id }}"}',
                'map' => 'different map',
                1 => 'fake file',
            ],
            'v1.1577964600.69dd1f396016e284afb221966ae5e61323a23222f2ad2a5086e4ba2354f99e58',
        ];

        yield 'no header' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            '',
            'Missing `Authorization` HTTP header in signed query',
        ];

        yield 'invalid header' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'foo',
            'Invalid `Authorization` HTTP header in signed query',
        ];

        yield 'no graphql operations' => [
            [$key1],
            '',
            null,
            'v1.1577964600.' . str_repeat('a', 64),
            'Could not find GraphQL operations in request',
        ];
    }
}
