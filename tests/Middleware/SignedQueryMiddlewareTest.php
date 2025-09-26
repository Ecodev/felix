<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Middleware;

use Cake\Chronos\Chronos;
use Ecodev\Felix\Middleware\SignedQueryMiddleware;
use Laminas\Diactoros\CallbackStream;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
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
    public function testRequiredSignedQuery(
        array $keys,
        string $body,
        ?array $parsedBody,
        string $signature,
        string|int|null $keyName = null,
        string $expectExceptionMessage = '',
        string $ip = '',
    ): void {
        $this->process($keys, true, $ip, $body, $parsedBody, $signature, $keyName, $expectExceptionMessage);
    }

    /**
     * @dataProvider dataProviderQuery
     */
    public function testNonRequiredSignedQuery(
        array $keys,
        string $body,
        ?array $parsedBody,
        string $signature,
    ): void {
        $this->process($keys, false, '', $body, $parsedBody, $signature, null, '');
    }

    public static function dataProviderQuery(): iterable
    {
        $key1 = 'my-secret-1';
        $key2 = 'my-secret-2';

        yield 'simple' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577964600.a4d664cd3d9903e4fecf6f9f671ad953586a7faeb16e67c306fd9f29999dfdd7',
            0,
        ];

        yield 'simple with key name' => [
            ['my custom key name' => $key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577964600.a4d664cd3d9903e4fecf6f9f671ad953586a7faeb16e67c306fd9f29999dfdd7',
            'my custom key name',
        ];

        yield 'simple but wrong key' => [
            [$key2],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577964600.a4d664cd3d9903e4fecf6f9f671ad953586a7faeb16e67c306fd9f29999dfdd7',
            null,
            'Invalid signed query',
        ];

        yield 'simple with all keys' => [
            [$key2, $key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577964600.a4d664cd3d9903e4fecf6f9f671ad953586a7faeb16e67c306fd9f29999dfdd7',
            1,
        ];

        yield 'simple but slightly in the past' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577951100.7d3b639703584e3ea4c68b30a37b56bcf94d19ccdc11c7f05a737c4e7e663a6c',
            0,
        ];

        yield 'simple but too much in the past' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577951099.' . str_repeat('a', 64),
            null,
            'Signed query is expired',
        ];

        yield 'simple but slightly in the future' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577978100.b6fb50cd1aa3974ec9df0c320bf32ff58a28f6fc2040aa13e529a7ef57212e49',
            0,
        ];

        yield 'simple but too much in the future' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577978101.' . str_repeat('a', 64),
            null,
            'Signed query is expired',
        ];

        yield 'batching' => [
            [$key1],
            '[{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }},{"operationName":"Configuration","variables":{"key":"announcement-active"},"query":"query Configuration($key: String!) { configuration(key: $key)}"}]',
            null,
            'v1.1577964600.566fafed794d956d662662b0df3d88e5c0a1e52e19111c08cc122f64a54bd8ec',
            0,
        ];

        yield 'file upload' => [
            [$key1],
            '',
            [
                'operations' => '{"operationName":"CreateImage","variables":{"input":{"file":null}},"query":"mutation CreateImage($input: ImageInput!) { createImage(input: $input) { id }}"}',
                'map' => '{"1":["variables.input.file"]}',
            ],
            'v1.1577964600.69dd1f396016e284afb221966ae5e61323a23222f2ad2a5086e4ba2354f99e58',
            0,
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
            0,
        ];

        yield 'no header' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            '',
            null,
            'Missing `X-Signature` HTTP header in signed query',
        ];

        yield 'invalid header' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'foo',
            null,
            'Invalid `X-Signature` HTTP header in signed query',
        ];

        yield 'no graphql operations with invalid signature is rejected' => [
            [$key1],
            '',
            null,
            'v1.1577964600.' . str_repeat('a', 64),
            null,
            'Invalid signed query',
        ];

        yield 'no graphql operations with correct signature is OK (but will be rejected later by GraphQL own validation mechanism)' => [
            [$key1],
            '',
            null,
            'v1.1577964600.ff8a9f2bc8090207b824d88251ed8e9d39434607d86e0f0b2837c597d6642c26',
            0,
            '',
        ];

        yield 'no header, but allowed IPv4' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            '',
            'no-attribute-at-all',
            '',
            '1.2.3.4',
        ];

        yield 'simple but wrong key will still error even if IP is allowed, because we want to be able to test signature even when allowed' => [
            [$key2],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577964600.a4d664cd3d9903e4fecf6f9f671ad953586a7faeb16e67c306fd9f29999dfdd7',
            null,
            'Invalid signed query',
            '1.2.3.4',
        ];

        yield 'no header, even GoogleBot is rejected, because GoogleBot should not forge new requests but only (re)play existing ones' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            '',
            null,
            'Missing `X-Signature` HTTP header in signed query',
            '66.249.70.134',
        ];

        yield 'too much in the past, but GoogleBot is allowed to replay old requests' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577951099.20177a7face4e05a75c4b2e41bc97a8225f420f5b7bb1709dd5499821dba0807',
            0,
            '',
            '66.249.70.134',
        ];

        yield 'too much in the past and invalid signature, even GoogleBot is rejected, because GoogleBot should not modify queries and their signatures' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577951099' . str_repeat('a', 64),
            null,
            'Invalid `X-Signature` HTTP header in signed query',
            '66.249.70.134',
        ];

        yield 'no header, BingBot is allowed, because BingBot does not seem to include our custom header in his request' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            '',
            'no-attribute-at-all',
            '',
            '40.77.188.165',
        ];

        yield 'too much in the past, even BingBot is rejected, because BingBot should not have any header at all' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577951099.20177a7face4e05a75c4b2e41bc97a8225f420f5b7bb1709dd5499821dba0807',
            null,
            'Signed query is expired',
            '40.77.188.165',
        ];

        yield 'too much in the past and invalid signature, even BingBot is rejected, because BingBot should not have any header at all' => [
            [$key1],
            '{"operationName":"CurrentUser","variables":{},"query":"query CurrentUser { viewer { id }}',
            null,
            'v1.1577951099' . str_repeat('a', 64),
            null,
            'Invalid `X-Signature` HTTP header in signed query',
            '40.77.188.165',
        ];
    }

    public function testThrowIfNoKeys(): void
    {
        $this->expectExceptionMessage('Signed queries are required, but no keys are configured');
        $this->expectExceptionCode(0);
        new SignedQueryMiddleware([], []);
    }

    private function process(
        array $keys,
        bool $required,
        string $ip,
        string $body,
        ?array $parsedBody,
        string $signature,
        string|int|null $keyName,
        string $expectExceptionMessage,
    ): void {
        $request = new ServerRequest(['REMOTE_ADDR' => $ip]);
        $request = $request->withBody(new CallbackStream(fn () => $body))->withParsedBody($parsedBody);

        if ($signature) {
            $request = $request->withHeader('X-Signature', $signature);
        }

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($expectExceptionMessage ? self::never() : self::once())
            ->method('handle')
            ->willReturnCallback(function (ServerRequestInterface $incomingRequest) use ($required, $body, $keyName) {
                self::assertSame($body, $incomingRequest->getBody()->getContents(), 'the original body content is still available for next middlewares');
                self::assertSame($required ? $keyName : 'no-attribute-at-all', $incomingRequest->getAttribute(SignedQueryMiddleware::class, 'no-attribute-at-all'), 'the name of the key used');

                return new Response();
            });

        $middleware = new SignedQueryMiddleware($keys, ['1.2.3.4', '2a01:198:603:0::/65'], $required);

        if ($expectExceptionMessage) {
            $this->expectExceptionMessage($expectExceptionMessage);
            $this->expectExceptionCode(403);
        }

        $middleware->process($request, $handler);
    }
}
